<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        if (DB::table('users')->where('email', $request->email)->exists()) {
            return response()->json(['message' => 'This email is already registered.'], 422);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('email_verification_codes')->where('email', $request->email)->delete();

        DB::table('email_verification_codes')->insert([
            'email'      => $request->email,
            'code'       => $code,
            'verified'   => false,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::mailer('smtp')->to($request->email)->send(new VerificationCodeMail($code));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send signup verification code: ' . $e->getMessage());

            return response()->json([
                'message' => 'Unable to send verification email right now. Please check your email address and try again.',
            ], 500);
        }

        return response()->json(['message' => 'Verification code sent.']);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'string'],
        ]);

        $record = DB::table('email_verification_codes')
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Incorrect code.'], 422);
        }

        if (now()->greaterThan($record->expires_at)) {
            return response()->json(['message' => 'This code has expired. Please request a new one.'], 422);
        }

        DB::table('email_verification_codes')
            ->where('email', $request->email)
            ->update(['verified' => true, 'updated_at' => now()]);

        return response()->json(['message' => 'Email verified.']);
    }
}