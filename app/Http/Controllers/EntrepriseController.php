<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;

class EntrepriseController extends Controller
{
    /**
     * Liste des entreprises.
     */
    public function index()
    {
        $entreprises = Entreprise::orderBy('nom')->get();

        return response()->json($entreprises);
    }

    /**
     * Créer une entreprise.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:entreprises,slug|alpha_dash',
        ]);

        $entreprise = Entreprise::create($validated);

        return response()->json($entreprise, 201);
    }

    public function show(Entreprise $entreprise)
    {
        return response()->json($entreprise);
    }

    public function update(Request $request, Entreprise $entreprise)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:entreprises,slug,' . $entreprise->id . '|alpha_dash',
        ]);

        $entreprise->update($validated);

        return response()->json($entreprise);
    }

    public function destroy(Entreprise $entreprise)
    {
        $entreprise->delete();

        return response()->json([
            'message' => 'Entreprise supprimée.'
        ]);
    }
}
