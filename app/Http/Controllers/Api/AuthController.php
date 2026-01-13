<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validation
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Vérification utilisateur
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects',
            ], 401);
        }

        // Restriction rôle
        if ($user->role !== 'sage_femme') {
            return response()->json([
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Supprimer anciens tokens (recommandé)
        $user->tokens()->delete();

        // Générer token
        $token = $user->createToken('flutter-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }
}
