<?php

namespace App\Http\Controllers\ApiV2;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Helper to resolve current authenticated user:
     * 1. Via Sanctum token
     * 2. Via header X-User-Id / X-Username
     * 3. Via query or body param user_id / username
     */
    public static function resolveCurrentUser(Request $request): ?User
    {
        // 1. Sanctum token
        if ($user = Auth::guard('sanctum')->user()) {
            return $user;
        }

        // 2. Header
        $userId = $request->header('X-User-Id') ?? $request->header('X-User-ID');
        if ($userId) {
            return User::find($userId);
        }

        $username = $request->header('X-Username');
        if ($username) {
            return User::where('username', $username)->first();
        }

        // 3. Request input
        if ($request->filled('user_id')) {
            return User::find($request->input('user_id'));
        }

        if ($request->filled('username')) {
            return User::where('username', $request->input('username'))->first();
        }

        return null;
    }

    /**
     * Login endpoint
     * POST /api/login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'sometimes|string',
            'username' => 'sometimes|string',
            'login'    => 'sometimes|string',
            'password' => 'required|string',
        ]);

        $identifier = $validated['login'] ?? $validated['username'] ?? $validated['email'] ?? null;

        if (!$identifier) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau email wajib diisi'
            ], 422);
        }

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak cocok. Periksa kembali username/email dan kata sandi Anda.'
            ], 401);
        }

        // Generate Sanctum token
        $token = $user->createToken('kasir-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => [
                'id'               => $user->id,
                'name'             => $user->name,
                'username'         => $user->username,
                'email'            => $user->email,
                'role'             => $user->role,
                'is_admin'         => $user->isAdmin(),
                'can_manage_stock' => $user->isAdmin(),
            ]
        ], 200);
    }

    /**
     * Get Current Profile
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = self::resolveCurrentUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Belum login / token tidak valid'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user'    => [
                'id'               => $user->id,
                'name'             => $user->name,
                'username'         => $user->username,
                'email'            => $user->email,
                'role'             => $user->role,
                'is_admin'         => $user->isAdmin(),
                'can_manage_stock' => $user->isAdmin(),
            ]
        ]);
    }

    /**
     * Logout endpoint
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * List all users (Admin view)
     * GET /api/users
     */
    public function users(Request $request)
    {
        $user = self::resolveCurrentUser($request);

        // Hanya admin yang bisa melihat daftar user lengkap
        if ($user && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Admin yang dapat melihat daftar pengguna'
            ], 403);
        }

        $users = User::all()->map(function ($u) {
            $totalTrx = $u->transactions()->count();
            $totalOmset = $u->transactions()->sum('total');

            return [
                'id'               => $u->id,
                'name'             => $u->name,
                'username'         => $u->username,
                'email'            => $u->email,
                'role'             => $u->role,
                'total_transaksi'  => $totalTrx,
                'total_omset'      => (float) $totalOmset,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $users
        ]);
    }
}
