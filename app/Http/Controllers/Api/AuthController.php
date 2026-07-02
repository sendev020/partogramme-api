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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects',
            ], 401);
        }

        // ✅ Bloquer seulement les comptes désactivés (plus de restriction de rôle)
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Compte désactivé, contactez votre administrateur',
            ], 403);
        }

        //$user->tokens()->delete();
        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('flutter-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'district' => $user->district, // ✅ nécessaire côté Flutter pour l'affichage/logique locale
                'poste_de_sante' => $user->poste_de_sante, // ✅ nécessaire côté Flutter pour l'affichage/logique locale
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
