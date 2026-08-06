<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Preuve_depense;
use Illuminate\Support\Facades\DB;

class Preuve_depenseController extends Controller
{
    // Le gestionnaire liste les preuves à vérifier
    public function enAttente()
    {
        $preuves = Preuve_depense::with('demande.user', 'demande.entreprise', 'demande.depense')
            ->where('statut', 'en_attente_verification')
            ->latest()
            ->get();

        return response()->json($preuves);
    }

    // Le gestionnaire valide la preuve
    public function valider(Request $request, Preuve_depense $preuve)
    {
        if ($preuve->statut !== 'en_attente_verification') {
            return response()->json(['message' => 'Cette preuve a déjà été traitée.'], 422);
        }

        DB::transaction(function () use ($preuve, $request) {
            $demande = $preuve->demande;
            $depense = $demande->depense;

            $ecart = $preuve->montant_declare - $demande->montant_estime;
            if ($depense && $ecart != 0) {
                $depense->update(['montant_reel' => $preuve->montant_declare]);
                $depense->caisse()->decrement('solde', $ecart);
            }

            $preuve->update([
                'statut' => 'valide',
                'verifie_par' => $request->user()->id,
                'verifie_at' => now(),
            ]);

            $demande->update(['statut' => 'terminee']);
        });

        $preuve->demande->user->notify(new \App\Notifications\DemandeValideeNotification($preuve->demande));

        return response()->json(['message' => 'Preuve validée, mission terminée.']);
    }

    // Le gestionnaire rejette la preuve
    public function rejeter(Request $request, Preuve_depense $preuve)
    {
        if ($preuve->statut !== 'en_attente_verification') {
            return response()->json(['message' => 'Cette preuve a déjà été traitée.'], 422);
        }

        $preuve->update([
            'statut' => 'rejete',
            'verifie_par' => $request->user()->id,
            'verifie_at' => now(),
            'commentaire' => $request->input('commentaire'),
        ]);

        $preuve->demande->update(['statut' => 'preuve_rejetee']);

        return response()->json(['message' => 'Preuve rejetée. Le demandeur doit en soumettre une nouvelle.']);
    }
}
