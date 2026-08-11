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
    private const STATUTS_ACTIFS = ['en_attente', 'acceptee', 'preuve_envoyee'];

    // Le demandeur crée une nouvelle demande
    public function store(Request $request, WhatsappService $whatsapp)
    {
        $demandeActive = Demande::where('user_id', $request->user()->id)
            ->whereIn('statut', self::STATUTS_ACTIFS)
            ->exists();

        if ($demandeActive) {
            return response()->json([
                'message' => 'Vous avez déjà une demande active. Terminez-la avant d\'en créer une nouvelle.',
            ], 422);
        }

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
            ->paginate(10); // 10 demandes par page

        return response()->json($demandes);
    }
    // Le gestionnaire liste les demandes à traiter
    public function enAttente()
    {
        $demandes = Demande::with('user', 'entreprise')
            ->whereIn('statut', ['en_attente', 'acceptee'])
            ->latest()
            ->paginate(20); // Pagination pour éviter de surcharger la réponse

        return response()->json($demandes);
    }
    public function historique()
    {
        $demandes = Demande::with([
            'user',
            'entreprise',
            'depense',
            'preuve',
        ])
            ->latest()
            ->paginate(10);

        return response()->json($demandes);
    }

    // Le gestionnaire accepte : débloque l'argent (mission pas encore terminée)

    public function accepter(Request $request, Demande $demande, WhatsappService $whatsapp)
    {
        if ($demande->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $validated = $request->validate([
            'caisse_id' => 'required|exists:caisses,id',
        ]);

        $caisse = Caisse::findOrFail($validated['caisse_id']);

        if ($caisse->entreprise_id !== $demande->entreprise_id) {
            return response()->json([
                'message' => 'Cette caisse n\'appartient pas à l\'entreprise du demandeur.',
            ], 422);
        }

        if ($caisse->solde < $demande->montant_estime) {
            return response()->json([
                'message' => 'Solde insuffisant dans cette caisse. Un emprunt inter-caisses est nécessaire avant d\'accepter.',
                'solde_disponible' => $caisse->solde,
            ], 422);
        }

        DB::transaction(function () use ($demande, $caisse, $request) {
            $demande->update(['statut' => 'acceptee']);

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
                'Votre demande "' . $demande->motif . '" a été acceptée. Vous pouvez retirer les fonds.'
            );
        }

        return response()->json([
            'message' => 'Demande acceptée.',
            'demande' => $demande->fresh('depense'),
        ]);
    }
    public function validerSansPreuve(Request $request, Demande $demande, WhatsappService $whatsapp)
    {
        if ($demande->statut !== 'acceptee') {
            return response()->json([
                'message' => 'Cette demande doit d\'abord être acceptée avant de pouvoir être clôturée sans preuve.',
            ], 422);
        }

        $validated = $request->validate([
            'commentaire_validation' => 'required|string|max:1000',
        ]);

        $demande->update([
            'statut' => 'terminee',
            'commentaire_validation' => $validated['commentaire_validation'],
        ]);

        if ($demande->user->telephone_whatsapp) {
            $whatsapp->envoyer(
                $demande->user->telephone_whatsapp,
                'Votre demande "' . $demande->motif . '" a été clôturée sans justificatif (validation exceptionnelle).'
            );
        }

        return response()->json([
            'message' => 'Demande validée sans preuve.',
            'demande' => $demande->fresh('depense'),
        ]);
    }

    public function rejeter(Request $request, Demande $demande, WhatsappService $whatsapp)
    {
        if ($demande->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $demande->update(['statut' => 'rejetee', 'commentaire_validation' => $request->commentaire_validation]);

        $demande->user->notify(new DemandeRejeteeNotification($demande));

        if ($demande->user->telephone_whatsapp) {
            $whatsapp->envoyer(
                $demande->user->telephone_whatsapp,
                'Votre demande "' . $demande->motif . '" a été rejetée. Vous pouvez soumettre une nouvelle demande.'
            );
        }

        return response()->json(['message' => 'Demande rejetée.', 'demande' => $demande]);
    }


    // Le demandeur soumet la preuve après achat
    public function soumettrePreuve(Request $request, Demande $demande)
    {
        if ($demande->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if (!in_array($demande->statut, ['acceptee', 'preuve_rejetee'])) {
            return response()->json(['message' => 'Cette demande ne peut pas encore recevoir de preuve.'], 422);
        }

        $validated = $request->validate([
            'montant_reel' => 'required|numeric|min:0',
            'preuve' => 'required|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $chemin = $request->file('preuve')->store('preuves', 'public');

        DB::transaction(function () use ($demande, $validated, $chemin, $request) {
            $demande->update(['statut' => 'preuve_envoyee']);

            $demande->preuve()->updateOrCreate(
                ['demande_id' => $demande->id],
                [
                    'chemin_fichier' => $chemin,
                    'montant_declare' => $validated['montant_reel'],
                    'soumis_par' => $request->user()->id,
                    'statut' => 'en_attente_verification',
                ]
            );
        });



        // Notifier le gestionnaire ici si besoin (TODO)

        return response()->json([
            'message' => 'Preuve soumise avec succès.',
            'demande' => $demande->fresh('preuve'),
        ]);
    }
}
