<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rider\LoginRequest;
use App\Http\Requests\Rider\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RiderAuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $token = $user->createToken('rider-app')->plainTextToken;

        return $this->apiResponse(true, 'Registration successful', [
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->apiResponse(false, 'Invalid credentials', null, 401);
        }

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $user->tokens()->where('name', 'rider-app')->delete();
        $token = $user->createToken('rider-app')->plainTextToken;

        return $this->apiResponse(true, 'Login successful', [
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->apiResponse(true, 'Logged out successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->apiResponse(true, 'Profile retrieved', $request->user());
    }
}
