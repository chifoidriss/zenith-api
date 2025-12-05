<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Country;
// use App\Models\Address;
// use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function countries()
    {
        return Country::orderBy('name_fr')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function updateFile(Request $request)
    {
        $user = User::findOrFail(auth()->id());

        if ($request->hasFile('avatar')) {
            $request->validate(['avatar' => 'image']);
            $user->avatar_url = $request->file('avatar')->store('users/avatars', 'public');
        }

        if ($request->hasFile('cover')) {
            $request->validate(['cover' => 'image']);
            $user->cover_url = $request->file('cover')->store('users/covers', 'public');
        }

        $user->save();
        return $user;
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = User::findOrFail(auth()->id());

        if ($request->has('first_name')) {
            $request->validate(['first_name' => 'required|string|min:2|max:50']);
            $user->first_name = $request->first_name;
        }

        if ($request->has('last_name')) {
            $request->validate(['last_name' => 'required|string|min:2|max:50']);
            $user->last_name = $request->last_name;
        }

        if ($request->has('title')) {
            $request->validate(['title' => 'required|string|min:2|max:50']);
            $user->title = $request->title;
        }

        if ($request->has('email')) {
            $request->validate(['email' => 'required|email|unique:users,email,'.$user->id]);
            $user->email = $request->email;
        }

        if ($request->has('phone')) {
            $request->validate(['phone' => 'required|unique:users,phone,'.$user->id]);
            $user->phone = $request->phone;
        }

        // if ($request->has('country_id') && $request->has('region_id') && $request->has('city')) {
        //     $request->validate([
        //         'country_id' => 'required|exists:countries,id',
        //         'region_id' => 'required|exists:regions,id',
        //         'city' => 'required'
        //     ]);

        //     $address = Address::firstOrNew([
        //         'user_id' => auth()->id()
        //     ]);
        //     $address->country_id = $request->country_id;
        //     $address->region_id = $request->region_id;
        //     $address->city = $request->city;
        //     $address->save();
        // }

        if ($request->has('birthday')) {
            $request->validate(['birthday' => 'nullable|date']);
            $user->birthday = $request->birthday;
        }

        if ($request->has('genre')) {
            $request->validate(['genre' => 'nullable|in:M,F']);
            $user->genre = $request->genre;
        }

        if ($request->has('username')) {
            $request->validate(['username' => 'required|string|min:3|max:20|unique:users,username,'.$user->id]);
            $user->username = $request->username;
        }

        if ($request->has('language')) {
            $request->validate(['language' => 'required']);
            $user->language = $request->language;
        }

        if ($request->has('languages')) {
            $request->validate(['languages' => 'required']);
            $user->languages = $request->languages;
        }

        if ($request->has('timezone')) {
            $request->validate(['timezone' => 'required']);
            $user->timezone = $request->timezone;
        }

        $user->save();
        return User::findOrFail(auth()->id());
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = User::findOrFail(auth()->id());

        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();
            return $user;
        } else {
            return response()->json([
                'message' => 'Mot de passe actuel incorrect.',
                'errors' => [
                    'password' => ['Mot de passe actuel incorrect.']
                ]
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
