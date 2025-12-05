<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Society;
use Illuminate\Http\Request;

class SocietyController extends Controller
{
    public function show()
    {
        return Society::find(1);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string',
            'address_name'=>'nullable|string',
            'email'=>'nullable|string',
            'phone'=>'nullable|string',
            'site'=>'nullable|string',
            'uid'=>'nullable|string',
            'registre'=>'nullable|string',
        ]);

        $society = Society::firstOrNew();

        $society->fill($data);
        $society->save();

        return $society;
    }
}
