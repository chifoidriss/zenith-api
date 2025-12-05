<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use Illuminate\Http\Request;

class DeviseController extends Controller
{
    public function index()
    {
        return Devise::paginate(100);
    }

    public function show($id)
    {
        return Devise::findOrFail($id);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
        ]);

        $devise = Devise::firstOrNew([
            'id' => $request->input('id')
        ]);

        $devise->fill($request->only([
            'name',
            'symbol',
            'devise',
            'unity',
            'status',
        ]));
        $devise->save();

        return $this->show($devise->id);
    }

    public function destroy($id)
    {
        $devise = Devise::findOrFail($id);
        return $devise->delete();
    }
}
