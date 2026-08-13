<?php

namespace App\Http\Controllers;

use App\Models\Poste;

class PosteController extends Controller
{
    public function index()
    {
        $postes = Poste::where('role', 'demandeur', 'gestionnaire', 'superviseur')
            ->orderBy('nom')
            ->get();

        return response()->json($postes);
    }
}
