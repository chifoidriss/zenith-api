<?php

namespace App\Http\Controllers\Article;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        return Unit::paginate(100);
    }

    public function show($id)
    {
        return Unit::findOrFail($id);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
        ]);

        $unit = Unit::firstOrNew([
            'id' => $request->input('id')
        ]);

        $unit->fill($request->only([
            'name',
            'unity',
            'code',
            'parent_id',
        ]));
        $unit->save();

        return $this->show($unit->id);
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        return $unit->delete();
    }
}
