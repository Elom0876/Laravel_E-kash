<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Emprunt;
use App\Models\Caisse;
use Illuminate\Support\Facades\DB;

class EmpruntController extends Controller
{
    // Le gestionnaire enregistre un emprunt entre les deux caisses
    public function store(Request $request)
    {
        $validated = $request->validate([
            'caisse_preteuse_id' => 'required|exists:caisses,id|different:caisse_emprunteuse_id',
            'caisse_emprunteuse_id' => 'required|exists:caisses,id',
            'montant' => 'required|numeric|min:1',
            'motif' => 'required|string|max:255',
        ]);

        $caissePreteuse = Caisse::findOrFail($validated['caisse_preteuse_id']);

        if ($caissePreteuse->solde < $validated['montant']) {
            return response()->json([
                'message' => 'La caisse prêteuse n\'a pas les fonds suffisants pour cet emprunt.',
                'solde_disponible' => $caissePreteuse->solde,
            ], 422);
        }

        $emprunt = DB::transaction(function () use ($validated, $request, $caissePreteuse) {
            $emprunt = Emprunt::create([
                ...$validated,
                'enregistre_par' => $request->user()->id,
                'statut' => 'en_cours',
            ]);

            $caissePreteuse->decrement('solde', $validated['montant']);

            $caisseEmprunteuse = Caisse::findOrFail($validated['caisse_emprunteuse_id']);
            $caisseEmprunteuse->increment('solde', $validated['montant']);

            return $emprunt;
        });

        return response()->json($emprunt, 201);
    }

    // Liste des emprunts, avec filtre optionnel sur le statut
    public function index(Request $request)
    {
        $emprunts = Emprunt::with('caissePreteuse.entreprise', 'caisseEmprunteuse.entreprise')
            ->when($request->statut, fn($q) => $q->where('statut', $request->statut))
            ->latest()
            ->get();

        return response()->json($emprunts);
    }

    // Régularisation : la caisse emprunteuse rembourse la caisse prêteuse
    public function rembourser(Emprunt $emprunt)
    {
        if ($emprunt->statut !== 'en_cours') {
            return response()->json(['message' => 'Cet emprunt est déjà remboursé.'], 422);
        }

        DB::transaction(function () use ($emprunt) {
            $emprunt->caisseEmprunteuse->decrement('solde', $emprunt->montant);
            $emprunt->caissePreteuse->increment('solde', $emprunt->montant);

            $emprunt->update([
                'statut' => 'rembourse',
                'rembourse_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Emprunt régularisé.', 'emprunt' => $emprunt->fresh()]);
    }
}
