<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{

    /**
     * Store a newly created User in storage.
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = new User($request->only([
            'first_name',
            'last_name',
            'email',
            'phone',
        ]));
        $user->password = Hash::make($request->password);
        $user->username = strtolower($request->first_name.$request->last_name);
        $user->save();

        $token = $user->createToken('app_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Auth User in storage.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $request->email)->first();
        } else {
            $user = User::where('phone', $request->email)->first();
        }

        if ($user && Hash::check($request->password, $user->password)) {
            $message = 'Authentification réussie.';
            $token = $user->createToken('app_token')->plainTextToken;

            $user->update(['last_logged_at' => now()]);

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => $message,
            ]);
        }

        return response()->json([
            'message' => 'Adresse email ou mot de passe incorrect.',
            'errors' => [
                'email' => ['Adresse email ou mot de passe incorrect.']
            ]
        ], 422);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function me()
    {
        return response()->json([
            'user' => request()->user()
        ]);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? back()->with(['status' => __($status)])
                    : back()->withErrors(['email' => __($status)]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withErrors(['email' => [__($status)]]);
    }

    public function verificationSend(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'status' => 'verification-link-sent',
            'message' => 'Verification link sent!'
        ]);
    }
}
