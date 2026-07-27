<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use Illuminate\Http\Request;

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
    public function approvisionner(Request $request, Caisse $caisse)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
        ]);

        $caisse->increment('solde', $validated['montant']);

        // TODO : enregistrer ce mouvement dans ta table 'approvionnements'

        return response()->json(['message' => 'Caisse approvisionnée.', 'caisse' => $caisse->fresh()]);
    }
}
