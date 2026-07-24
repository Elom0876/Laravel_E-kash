<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'telephone_whatsapp' => 'required|regex:/^\+?[0-9]{8,15}$/',
            'poste_id' => 'required|exists:postes,id',
            'entreprise_id' => 'required|exists:entreprises,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'telephone_whatsapp' => $validated['telephone_whatsapp'],
            'poste_id' => $validated['poste_id'],
            'entreprise_id' => $validated['entreprise_id'],
        ]);

        $user->load('poste', 'entreprise');

        return response()->json([
            'user' => $user,
            'role' => $user->role,
            'entreprise' => $user->entreprise?->slug,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $user->load('poste', 'entreprise');

        return response()->json([
            'user' => $user,
            'role' => $user->role, // vient de l'accesseur getRoleAttribute()
            'entreprise' => $user->entreprise?->slug,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('poste', 'entreprise'),
            'role' => $request->user()->role,
        ]);
    }
}
