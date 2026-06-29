<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Liste tous les utilisateurs (admin + superviseurs)
     */
    public function index(Request $request)
    {

     /** @var \App\Models\User $user */
        $user = Auth::user();

        // Seuls les admins et superviseurs peuvent voir la liste
        if (!$user->isAdmin() && !$user->isSuperviseur() && !$user->isSuperviseurRegional()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = User::query();

        // Les superviseurs ne voient que les users de leur district
        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        }
        // Les superviseurs régionaux peuvent filtrer par district s'ils le demandent
        elseif ($user->isSuperviseurRegional() && $request->filled('district')) {
            $query->where('district', $request->district);
        }
        // Les admins voient tous

        return response()->json($query->get(), 200);
    }

    /**
     * Récupère un utilisateur par ID
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Vérifier les permissions
        if (!$user->isAdmin() && $user->district !== $targetUser->district) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json($targetUser, 200);
    }

    /**
     * Crée un nouvel utilisateur (admin uniquement)
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Seuls les admins peuvent créer des utilisateurs
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:admin,superviseur,superviseur_regional,sage_femme',
                'district' => 'nullable|string|max:255',
                'poste_de_sante' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'district' => $validated['district'] ?? null,
                'poste_de_sante' => $validated['poste_de_sante'] ?? null,
                'is_active' => isset($validated['is_active']) ? ($validated['is_active'] ? 1 : 0) : 0,
            ]);

            return response()->json($newUser, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour un utilisateur (admin ou l'utilisateur lui-même)
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Seuls l'admin et l'utilisateur lui-même peuvent modifier
        if (!$authUser->isAdmin() && $authUser->id !== (int) $id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:8',
                'role' => 'nullable|in:admin,superviseur,superviseur_regional,sage_femme',
                'district' => 'nullable|string|max:255',
                'poste_de_sante' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // Ne pas permettre aux non-admins de changer le rôle
            if (!$authUser->isAdmin() && isset($validated['role'])) {
                unset($validated['role']);
            }

            // Hasher le password s'il est fourni
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $targetUser->update(array_filter($validated));

            return response()->json($targetUser, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Supprime un utilisateur (admin uniquement)
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Seuls les admins peuvent supprimer
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Empêcher de se supprimer soi-même
        if ($user->id === (int) $id) {
            return response()->json(['message' => 'Impossible de vous supprimer vous-même'], 400);
        }

        try {
            $targetUser->delete();
            return response()->json(['message' => 'Utilisateur supprimé avec succès'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }
}
