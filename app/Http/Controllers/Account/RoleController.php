<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RoleController extends Controller
{
    public function permissions()
    {
        return Permission::all();
    }

    public function index()
    {
        return Role::paginate(100);
    }

    public function show($id)
    {
        return Role::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'description'=>'nullable|string',
        ]);

        $role = Role::firstOrNew([
            'id' => $request->input('id')
        ]);

        $role->fill($request->only([
            'name',
            'description',
        ]));
        $role->save();
        $role->permissions()->sync($request->permissions_id);

        return $this->show($role->id);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        return $role->delete();
    }
}
