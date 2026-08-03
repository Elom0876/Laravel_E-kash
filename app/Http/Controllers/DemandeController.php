<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Demande;
use App\Models\Depense;
use App\Models\Caisse;
use App\Models\User;
use App\Notifications\NouvelleDemandeNotification;
use App\Notifications\DemandeValideeNotification;
use App\Notifications\DemandeRejeteeNotification;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\DB;

class DemandeController extends Controller
{
    // Le demandeur crée une nouvelle demande
    public function store(Request $request, WhatsappService $whatsapp)
    {
        $validated = $request->validate([
            'motif' => 'required|string|max:255',
            'montant_estime' => 'required|numeric|min:1',
        ]);

        $demande = Demande::create([
            'user_id' => $request->user()->id,
            'entreprise_id' => $request->user()->entreprise_id,
            'motif' => $validated['motif'],
            'montant_estime' => $validated['montant_estime'],
            'statut' => 'en_attente',
        ]);

        // Notifier tous les gestionnaires de la même entreprise
        $gestionnaires = User::whereHas('poste', fn($q) => $q->where('role', 'gestionnaire'))
            ->where('entreprise_id', $demande->entreprise_id)
            ->get();

        foreach ($gestionnaires as $gestionnaire) {
            $gestionnaire->notify(new NouvelleDemandeNotification($demande));

            if ($gestionnaire->telephone_whatsapp) {
                $whatsapp->envoyer(
                    $gestionnaire->telephone_whatsapp,
                    'Nouvelle demande de ' . $demande->user->name . ' : ' . $demande->motif .
                    ' (' . number_format($demande->montant_estime, 0, ',', ' ') . ' FCFA)'
                );
            }
        }

        return response()->json($demande, 201);
    }

    // Le demandeur consulte l'historique de ses propres demandes
    public function mesDemandes(Request $request)
    {
        $demandes = Demande::with('depense', 'preuve')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($demandes);
    }

    // Le gestionnaire liste les demandes en attente
    public function enAttente()
    {
        $demandes = Demande::with('user', 'entreprise')
            ->where('statut', 'en_attente')
            ->latest()
            ->get();

        return response()->json($demandes);
    }

    // Le gestionnaire valide une demande
    public function valider(Request $request, Demande $demande, WhatsappService $whatsapp)
    {
        if ($demande->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $caisse = Caisse::where('entreprise_id', $demande->entreprise_id)->firstOrFail();

        if ($caisse->solde < $demande->montant_estime) {
            return response()->json([
                'message' => 'Solde insuffisant dans cette caisse. Un emprunt inter-caisses est nécessaire avant de valider.',
                'solde_disponible' => $caisse->solde,
            ], 422);
        }

        DB::transaction(function () use ($demande, $caisse, $request) {
            $demande->update(['statut' => 'validee']);

            Depense::create([
                'demande_id' => $demande->id,
                'caisse_id' => $caisse->id,
                'montant_reel' => $demande->montant_estime,
                'enregistre_par' => $request->user()->id,
            ]);

            $caisse->decrement('solde', $demande->montant_estime);
        });

        $demande->user->notify(new DemandeValideeNotification($demande));

        if ($demande->user->telephone_whatsapp) {
            $whatsapp->envoyer(
                $demande->user->telephone_whatsapp,
                'Votre demande "' . $demande->motif . '" a été validée. Vous pouvez retirer les fonds.'
            );
        }

        return response()->json([
            'message' => 'Demande validée.',
            'demande' => $demande->fresh('depense'),
        ]);
    }

    // Le gestionnaire rejette une demande
    public function rejeter(Request $request, Demande $demande, WhatsappService $whatsapp)
    {
        if ($demande->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $demande->update(['statut' => 'rejetee']);

        $demande->user->notify(new DemandeRejeteeNotification($demande));

        if ($demande->user->telephone_whatsapp) {
            $whatsapp->envoyer(
                $demande->user->telephone_whatsapp,
                'Votre demande "' . $demande->motif . '" a été rejetée.'
            );
        }

        return response()->json([
            'message' => 'Demande rejetée.',
            'demande' => $demande,
        ]);
    }

    // Le demandeur soumet la preuve après achat
    public function soumettrePreuve(Request $request, Demande $demande)
    {
        if ($demande->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if ($demande->statut !== 'validee') {
            return response()->json(['message' => 'Cette demande ne peut pas encore être justifiée.'], 422);
        }

        $validated = $request->validate([
            'montant_reel' => 'required|numeric|min:0',
            'preuve' => 'required|image|max:5120', // 5 Mo max
        ]);

        $chemin = $request->file('preuve')->store('preuves', 'public');

        DB::transaction(function () use ($demande, $validated, $chemin, $request) {
            $depense = $demande->depense;
            $ecart = $validated['montant_reel'] - $demande->montant_estime;

            $depense->update(['montant_reel' => $validated['montant_reel']]);

            if ($ecart !== 0.0) {
                $depense->caisse()->decrement('solde', $ecart);
            }

            $demande->update(['statut' => 'justifiee']);

            $demande->preuve()->create([
                'chemin_fichier' => $chemin,
                'montant_declare' => $validated['montant_reel'],
                'soumis_par' => $request->user()->id,
            ]);
        });

        return response()->json([
            'message' => 'Preuve soumise avec succès.',
            'demande' => $demande->fresh('depense', 'preuve'),
        ]);
    }
}