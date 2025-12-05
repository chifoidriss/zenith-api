<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\LeavingType;
use Illuminate\Http\Request;

class LeavingTypeController extends Controller
{
    public function index()
    {
        return LeavingType::paginate(100);
    }

    public function show($id)
    {
        return LeavingType::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'description'=>'nullable|string',
        ]);

        $leavingType = LeavingType::firstOrNew([
            'id' => $request->input('id')
        ]);

        $leavingType->fill($request->only([
            'name',
            'description',
        ]));
        $leavingType->save();

        return $this->show($leavingType->id);
    }

    public function destroy($id)
    {
        $leavingType = LeavingType::findOrFail($id);
        return $leavingType->delete();
    }
}
