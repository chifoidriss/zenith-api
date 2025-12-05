<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Indemnity;
use Illuminate\Http\Request;

class IndemnityController extends Controller
{
    public function index()
    {
        return Indemnity::paginate(100);
    }

    public function show($id)
    {
        return Indemnity::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'description'=>'nullable|string',
        ]);

        $indemnity = Indemnity::firstOrNew([
            'id' => $request->input('id')
        ]);

        $indemnity->fill($request->only([
            'name',
            'description',
        ]));
        $indemnity->save();

        return $this->show($indemnity->id);
    }

    public function destroy($id)
    {
        $indemnity = Indemnity::findOrFail($id);
        return $indemnity->delete();
    }
}
