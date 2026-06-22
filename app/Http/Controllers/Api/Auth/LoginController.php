<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group المصادقة (Authentication)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 */
class LoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = \App\Models\User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.credentials_incorrect')],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('messages.account_deactivated')],
            ]);
        }

        $token = $user->createToken('evona-token')->plainTextToken;
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => __('messages.logged_in'),
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }
}
