<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

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
}
