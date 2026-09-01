<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use Illuminate\Http\Request;
use App\Models\Approvisionnement;
use Illuminate\Support\Facades\DB;

class CaisseController extends Controller
{
    // Vue d'ensemble des deux caisses (pour gestionnaire + superviseur)
    public function index()
    {
        $caisses = Caisse::with('entreprise')->get();

        return response()->json($caisses);
    }

    // Détail d'une caisse avec son historique de mouvements
    public function show(Caisse $caisse)
    {
        $caisse->load('entreprise');

        $depenses = $caisse->depenses()->with('demande.user')->latest()->get();

        return response()->json([
            'caisse' => $caisse,
            'mouvements' => $depenses,
        ]);
    }

    // Approvisionnement (entrée d'argent dans la caisse)

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id', // "unique:caisses,entreprise_id" retiré
            'nom' => 'required|string|max:255',
            'solde' => 'nullable|numeric|min:0',
        ]);

        $caisse = Caisse::create([
            'entreprise_id' => $validated['entreprise_id'],
            'nom' => $validated['nom'],
            'solde' => $validated['solde'] ?? 0,
        ]);

        return response()->json($caisse, 201);
    }
    public function parEntreprise(Request $request, $entrepriseId)
    {
        $caisses = Caisse::where('entreprise_id', $entrepriseId)->get();
        return response()->json($caisses);
    }
    public function approvisionner(Request $request, Caisse $caisse)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
            'source_type' => 'required|in:directe,indirecte',

            // Obligatoire uniquement si source directe (virement bancaire)
            'compte_bancaire' => 'required_if:source_type,directe|nullable|string|max:255',

            // Obligatoire uniquement si source indirecte (personnel)
            'mode_reglement' => 'required_if:source_type,indirecte|nullable|in:cheque,espece',

            // Obligatoire uniquement si mode = chèque
            'numero_cheque' => 'required_if:mode_reglement,cheque|nullable|string|max:100',

            // Obligatoire uniquement si mode = espèces
            'depose_par' => 'required_if:mode_reglement,espece|nullable|string|max:255',
        ]);

        DB::transaction(function () use ($caisse, $validated, $request) {
            Approvisionnement::create([
                'caisse_id' => $caisse->id,
                'montant' => $validated['montant'],
                'motif' => $validated['motif'] ?? null,
                'enregistre_par' => $request->user()->id,
                'date_approvisionnement' => now(),
                'source_type' => $validated['source_type'],
                'compte_bancaire' => $validated['compte_bancaire'] ?? null,
                'mode_reglement' => $validated['mode_reglement'] ?? null,
                'numero_cheque' => $validated['numero_cheque'] ?? null,
                'depose_par' => $validated['depose_par'] ?? null,
            ]);

            $caisse->increment('solde', $validated['montant']);
        });

        return response()->json([
            'message' => 'Caisse approvisionnée.',
            'caisse' => $caisse->fresh(),
        ]);
    }
    public function historiqueApprovisionnements(Caisse $caisse)
    {
        $approvisionnements = $caisse->approvisionnements()
            ->with('enregistrePar')
            ->latest()
            ->get();

        return response()->json($approvisionnements);
    }
}
