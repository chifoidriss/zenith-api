<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return User::paginate(100);
    }

    public function show($id)
    {
        return User::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'=>'required|string',
            'last_name'=>'required|string',
            'title'=>'required|string',
            'email'=>'required|email',
            'phone'=>'required|string',
        ]);

        $user = User::firstOrNew([
            'id' => $request->input('id')
        ]);

        $user->fill($data);
        if (!$user->password) {
            $user->password = Hash::make(12345678);
        }
        $user->save();

        // $user->roles()->sync($request->roles_id);

        foreach ($request->permis as $item) {
            if($item["value"]) {
                $p = DB::table('permission_user')->where([
                    'user_id' => $user->id,
                    'permission_id' => $item["id"],
                ])->first();
                if ($p) {
                    DB::table('permission_user')->where([
                        'user_id' => $user->id,
                        'permission_id' => $item["id"],
                    ])->update([
                        'value' => $item["value"]
                    ]);
                } else {
                    DB::table('permission_user')->insert([
                        'user_id' => $user->id,
                        'permission_id' => $item["id"],
                        'value' => $item["value"],
                    ]);
                }
            } else {
                $p = DB::table('permission_user')->where([
                    'user_id' => $user->id,
                    'permission_id' => $item["id"],
                ])->delete();
            }
        }

        return $this->show($user->id);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        return $user->delete();
    }
}
