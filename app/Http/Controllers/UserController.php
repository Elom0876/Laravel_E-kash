<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;

class UserController extends Controller
{
    // Le gestionnaire/superviseur ajoute un nouvel employé
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone_whatsapp' => 'required|regex:/^\+?[0-9]{8,15}$/',
            'poste_id' => 'required|exists:postes,id',
            'entreprise_id' => 'required|exists:entreprises,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone_whatsapp' => $validated['telephone_whatsapp'],
            'poste_id' => $validated['poste_id'],
            'entreprise_id' => $validated['entreprise_id'],
            'password' => Hash::make(Str::random(40)), // mot de passe temporaire inutilisable
        ]);

        // Envoie le lien de définition de mot de passe (même mécanisme que "mot de passe oublié")
        Password::sendResetLink(['email' => $user->email]);

        return response()->json([
            'message' => 'Employé créé. Un email a été envoyé pour définir son mot de passe.',
            'user' => $user,
        ], 201);
    }
    public function index(Request $request)
    {
        $users = User::with(['entreprise', 'poste'])
            ->when(
                $request->entreprise_id,
                fn($query) =>
                $query->where('entreprise_id', $request->entreprise_id)
            )
            ->latest()
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }
}
