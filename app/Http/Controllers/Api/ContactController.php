<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:5000',
        ]);

        $contactMessage = ContactMessage::create($validated);

        try {
            Mail::to(config('mail.from.address'))->send(new ContactMail($contactMessage));
        } catch (\Exception $e) {
            // Log email error but don't fail the request
        }

        return response()->json([
            'success' => true,
            'message' => 'تم استلام رسالتك، سنتواصل معك قريباً',
        ]);
    }
}
