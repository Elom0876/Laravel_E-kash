<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

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
    public function motDePasseOublie(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $statut = Password::sendResetLink($request->only('email'));

        return $statut === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Lien de réinitialisation envoyé par email.'])
            : response()->json(['message' => 'Impossible d\'envoyer le lien.'], 422);
    }

    public function reinitialiserMotDePasse(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $statut = Password::reset(
            $validated,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'password_set_at' => now(),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $statut === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Mot de passe défini avec succès.'])
            : response()->json(['message' => 'Token invalide ou expiré.'], 422);
    }
}
