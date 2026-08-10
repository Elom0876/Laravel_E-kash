<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EntrepriseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
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

    /**
     * Display the specified resource.
     */
    public function show(Entreprise $entreprise)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entreprise $entreprise)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entreprise $entreprise)
    {
        //
    }
}
