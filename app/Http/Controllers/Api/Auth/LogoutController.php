<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group المصادقة (Authentication)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 */
class LogoutController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.logged_out'),
        ]);
    }
}
