<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\ReasonLeaving;
use Illuminate\Http\Request;

class ReasonLeavingController extends Controller
{
    public function index()
    {
        return ReasonLeaving::paginate(100);
    }

    public function show($id)
    {
        return ReasonLeaving::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'description'=>'nullable|string',
        ]);

        $reasonLeaving = ReasonLeaving::firstOrNew([
            'id' => $request->input('id')
        ]);

        $reasonLeaving->fill($request->only([
            'name',
            'description',
        ]));
        $reasonLeaving->save();

        return $this->show($reasonLeaving->id);
    }

    public function destroy($id)
    {
        $reasonLeaving = ReasonLeaving::findOrFail($id);
        return $reasonLeaving->delete();
    }
}
