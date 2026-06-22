<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $token = Str::random(60);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $validated['email']],
                ['token' => bcrypt($token), 'created_at' => now()]
            );

            $resetUrl = config('app.frontend_url') . '/reset-password?'
                . http_build_query(['email' => $validated['email'], 'token' => $token]);

            Mail::to($user->email)->send(new PasswordResetMail($resetUrl));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.reset_sent'),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (!$record || !password_verify($validated['token'], $record->token)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_token'),
            ], 400);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->diffInMinutes(now()) > 60) {
            return response()->json([
                'success' => false,
                'message' => __('messages.token_expired'),
            ], 400);
        }

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_token'),
            ], 400);
        }

        $user->update(['password' => bcrypt($validated['password'])]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.password_reset'),
        ]);
    }
}
