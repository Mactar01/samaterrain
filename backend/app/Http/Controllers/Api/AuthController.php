<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/register
    // ─────────────────────────────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'max:150', 'unique:users'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'password'              => ['required', 'confirmed', Password::min(8)],
            // 'role' est désormais forcé à 'player' côté serveur
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($data['password']),
            'role'      => 'player', // <- Forcé à 'player'
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'user'    => $this->userResource($user),
            'token'   => $token,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/login
    // ─────────────────────────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez le support.',
            ], 403);
        }

        // Révoquer les anciens tokens et créer un nouveau
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'user'    => $this->userResource($user),
            'token'   => $token,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/logout
    // ─────────────────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/auth/me
    // ─────────────────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('owner');

        return response()->json([
            'user' => $this->userResource($user),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // PUT /api/v1/auth/profile
    // ─────────────────────────────────────────────────────────────────
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'                     => ['sometimes', 'string', 'max:100'],
            'phone'                    => ['sometimes', 'nullable', 'string', 'max:20'],
            'current_password'         => ['required_with:new_password', 'string'],
            'new_password'             => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Vérification mot de passe actuel si changement demandé
        if (!empty($data['new_password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Le mot de passe actuel est incorrect.'],
                ]);
            }
            $user->password = Hash::make($data['new_password']);
        }

        if (isset($data['name']))  $user->name  = $data['name'];
        if (array_key_exists('phone', $data)) $user->phone = $data['phone'];

        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'user'    => $this->userResource($user->fresh('owner')),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helper : formater la réponse utilisateur
    // ─────────────────────────────────────────────────────────────────
    private function userResource(User $user): array
    {
        $data = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'role'       => $user->role,
            'avatar'     => $user->avatar,
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at,
        ];

        if ($user->isOwner() && $user->relationLoaded('owner') && $user->owner) {
            $data['owner'] = [
                'id'            => $user->owner->id,
                'business_name' => $user->owner->business_name,
                'is_verified'   => $user->owner->isVerified(),
            ];
        }

        return $data;
    }
}
