<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Owner;
use App\Models\Field;
use App\Models\Reservation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * Obtenir les statistiques du super admin.
     */
    public function getStats(): JsonResponse
    {
        return response()->json([
            'total_owners' => Owner::count(),
            'total_fields' => Field::count(),
            'total_players' => User::where('role', 'player')->count(),
            'total_reservations' => Reservation::count(),
        ]);
    }

    /**
     * Liste des partenaires loueurs.
     */
    public function getOwners(): JsonResponse
    {
        $owners = Owner::with('user')->get();
        return response()->json($owners);
    }

    /**
     * Crée un compte Loueur (Accessible uniquement par l'admin).
     */
    public function createOwner(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'max:150', 'unique:users'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'password'              => ['required', 'confirmed', Password::min(8)],
            'business_name'         => ['required', 'string', 'max:200']
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($data['password']),
            'role'      => 'owner',
            'is_active' => true,
        ]);

        $owner = Owner::create([
            'user_id' => $user->id,
            'business_name' => $data['business_name'],
            'is_verified' => true
        ]);

        return response()->json([
            'message' => 'Compte loueur créé avec succès.',
            'user'    => $user->load('owner'),
        ], 201);
    }

    /**
     * Mettre à jour un partenaire.
     */
    public function updateOwner(Request $request, $id): JsonResponse
    {
        $owner = Owner::findOrFail($id);
        $user = $owner->user;

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:100'],
            'email'         => ['sometimes', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'phone'         => ['nullable', 'string', 'max:20'],
            'business_name' => ['sometimes', 'string', 'max:200']
        ]);

        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (array_key_exists('phone', $data)) $user->phone = $data['phone'];
        $user->save();

        if (isset($data['business_name'])) {
            $owner->business_name = $data['business_name'];
            $owner->save();
        }

        return response()->json([
            'message' => 'Partenaire mis à jour avec succès.',
            'owner'   => $owner->load('user')
        ]);
    }

    /**
     * Activer/Désactiver un partenaire.
     */
    public function toggleOwnerStatus($id): JsonResponse
    {
        $owner = Owner::findOrFail($id);
        $user = $owner->user;
        
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activé' : 'désactivé';

        return response()->json([
            'message' => "Le compte partenaire a été $status."
        ]);
    }

    /**
     * Supprimer un partenaire.
     */
    public function deleteOwner($id): JsonResponse
    {
        $owner = Owner::findOrFail($id);
        $user = $owner->user;
        
        $owner->delete();
        $user->delete();

        return response()->json([
            'message' => 'Partenaire supprimé avec succès.'
        ]);
    }
}
