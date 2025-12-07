<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login user (email, password, branch_id)
     * Returns token (Sanctum) and user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            // branch_id no longer required
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // If you previously required branch_id, now we auto-detect:
        // If user has no branch and is not admin, block login (business rule)
        if (empty($user->branch_id) && ($user->role ?? '') !== 'admin') {
            // optionally: allow login but with no branch? we choose to block for safety
            Auth::logout();
            return response()->json(['message' => 'User not assigned to any branch'], 403);
        }

        // create token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('branch'),
            'token' => $token,
        ]);
    }


    /**
     * Logout current token
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Get authenticated user profile
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('branch'));
    }
}
