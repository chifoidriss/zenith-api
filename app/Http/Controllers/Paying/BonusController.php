<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    public function index()
    {
        return Bonus::paginate(100);
    }

    public function show($id)
    {
        return Bonus::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $bonus = Bonus::firstOrNew([
            'id' => $request->input('id')
        ]);

        $bonus->fill($data);
        $bonus->save();

        return $this->show($bonus->id);
    }

    public function destroy($id)
    {
        $bonus = Bonus::findOrFail($id);
        return $bonus->delete();
    }
}
