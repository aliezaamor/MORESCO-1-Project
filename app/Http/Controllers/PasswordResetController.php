<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetCode;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'We cannot find a user with that email address.']);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'token' => Hash::make($code),
            'created_at' => Carbon::now()
        ]
        );

        Mail::to($request->email)->send(new PasswordResetCode($code));

        return redirect()->route('password.verify', ['email' => $request->email])
            ->with('success', 'A 6-digit reset code has been sent to your email.');
    }

    public function showVerifyForm(Request $request)
    {
        return view('auth.verify-code', ['email' => $request->email]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->code, $record->token)) {
            return back()->withErrors(['code' => 'The provided code is invalid.']);
        }

        // Check expiry (e.g., 60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return back()->withErrors(['code' => 'The code has expired.']);
        }

        return redirect()->route('password.reset', [
            'token' => $request->code, // We use the code as token for simplicity
            'email' => $request->email
        ]);
    }

    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password')->with(
        ['token' => $token, 'email' => $request->email]
        );
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid token or code.']);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password)
        ]);

        $user->setRememberToken(Str::random(60));
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('success', 'Your password has been reset!');
    }
}
