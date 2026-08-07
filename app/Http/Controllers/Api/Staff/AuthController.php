<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Staff\StaffUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->hasAnyRole([
            StaffRole::SuperAdmin->value,
            StaffRole::Admin->value,
            StaffRole::Production->value,
        ])) {
            throw ValidationException::withMessages([
                'email' => ['This account does not have staff access.'],
            ]);
        }

        $token = $user->createToken($validated['device_name'] ?? 'staff')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => (new StaffUserResource($user))->resolve(),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'data' => [
                'message' => 'Logged out',
            ],
        ]);
    }

    public function me(Request $request): StaffUserResource
    {
        return new StaffUserResource($request->user());
    }
}
