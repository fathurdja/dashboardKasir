<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and issue Sanctum token.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:admin,kasir,delivery',
            'device_name' => 'required|string',
            'platform' => 'nullable|string|in:android,ios,web',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->input('role', 'kasir'),
        ]);

        $device = Device::create([
            'user_id' => $user->id,
            'device_name' => $request->device_name,
            'platform' => $request->input('platform', 'android'),
            'is_active' => true,
        ]);

        $token = $user->createToken($request->device_name)->plainTextToken;

        $store = StoreSettings::current();

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ],
            'device' => [
                'id' => $device->id,
                'device_name' => $device->device_name,
                'platform' => $device->platform,
            ],
            'store' => $store ? [
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'tax_rate' => (float) $store->tax_rate,
                'currency' => $store->currency,
                'store_code' => $store->store_code,
            ] : null,
        ], 201);
    }
    /**
     * Login kasir and issue Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create or find device
        $device = Device::firstOrCreate(
            ['user_id' => $user->id, 'device_name' => $request->device_name],
            ['platform' => $request->input('platform', 'android'), 'is_active' => true]
        );

        // Revoke old tokens for this device name, keep it clean
        $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        $store = StoreSettings::current();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role ?? 'kasir',
            ],
            'device' => [
                'id' => $device->id,
                'device_name' => $device->device_name,
                'platform' => $device->platform,
            ],
            'store' => $store ? [
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'tax_rate' => (float) $store->tax_rate,
                'currency' => $store->currency,
                'store_code' => $store->store_code,
            ] : null,
        ]);
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Verify if the provided Bearer token is valid without throwing 401 via middleware.
     */
    public function verifyToken(Request $request): JsonResponse
    {
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            
            // Optionally, return the current token if needed, or just status
            $token = $request->bearerToken();

            return response()->json([
                'authenticated' => true,
                'message' => 'Token is valid',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role ?? 'kasir',
                ]
            ], 200);
        }

        return response()->json([
            'authenticated' => false,
            'message' => 'Token is invalid or expired',
        ], 401);
    }

    /**
     * Get authenticated user info + store settings.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = StoreSettings::current();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role ?? 'kasir',
            ],
            'store' => $store ? [
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'tax_rate' => (float) $store->tax_rate,
                'currency' => $store->currency,
                'store_code' => $store->store_code,
            ] : null,
        ]);
    }

    /**
     * Check if token is valid and return a fresh token (refresh mechanism).
     */
    public function checkToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Sanctum tokens don't expire by default unless configured.
        // But to implement a refresh mechanism, we can issue a new token
        // and revoke the current one.
        $currentAccessToken = $user->currentAccessToken();
        $deviceName = $currentAccessToken->name ?? 'mobile_device';
        
        // Revoke the current token
        $currentAccessToken->delete();
        
        // Issue a new token
        $newToken = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $newToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role ?? 'kasir',
            ]
        ], 200);
    }

    /**
     * Register a new device for the authenticated user.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $request->validate([
            'device_name' => 'required|string|max:255',
            'platform' => 'nullable|string|in:android,ios,web',
        ]);

        $device = Device::create([
            'user_id' => $request->user()->id,
            'device_name' => $request->device_name,
            'platform' => $request->input('platform', 'android'),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Device registered successfully',
            'device' => $device,
        ], 201);
    }
}
