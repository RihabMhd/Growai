<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class PasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email']
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Generate plain token (send to frontend)
        $plainToken = Str::random(64);

        // Store HASHED token in DB
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => hash('sha256', $plainToken),
                'created_at' => now()
            ]
        );

        // Send the email with the reset token
        \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\ResetPasswordMail($plainToken));

        return response()->json([
            'message' => 'Reset token generated and emailed successfully'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required'],
            'password' => ['required', 'min:6', 'confirmed']
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Invalid request'
            ], 400);
        }

        // verify token
        if (hash('sha256', $request->token) !== $record->token) {
            return response()->json([
                'message' => 'Invalid token'
            ], 400);
        }

        // optional expiry (60 min)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'message' => 'Token expired'
            ], 400);
        }

        // update password
        $user = User::where('email', $request->email)->first();

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // delete token after success
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password reset successful'
        ]);
    }
}
