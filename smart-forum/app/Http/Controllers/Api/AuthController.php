<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * Status: Pending admin approval by default.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'sometimes|in:admin,lecturer,student',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'student',
            'status' => 'active',
            'is_approved' => false,
            'last_communicated_at' => now(),
        ]);

        return response()->json([
            'message' => 'User registered successfully. Awaiting admin approval.',
            'user' => $user
        ], 201);
    }

    /**
     * Login a user and issue an API token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->is_approved) {
            return response()->json(['message' => 'Account pending admin approval.'], 403);
        }

        if ($user->status === 'blacklisted') {
            return response()->json(['message' => 'Your account has been blacklisted.'], 403);
        }

        $user->update(['last_communicated_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Get the authenticated user's details.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout (revoke the token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Admin: Approve a user.
     */
    public function approveUser(Request $request, $userId)
    {
        $admin = $request->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user = User::findOrFail($userId);
        $user->update(['is_approved' => true]);

        return response()->json(['message' => 'User approved successfully.', 'user' => $user]);
    }
}