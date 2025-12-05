<?php

namespace App\Http\Controllers\Stocking;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WareHouseController extends Controller
{
    public function index()
    {
        return Warehouse::paginate(100);
    }

    public function show($id)
    {
        return Warehouse::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'description'=>'required|string',
        ]);

        $warehouse = Warehouse::firstOrNew([
            'id' => $request->input('id')
        ]);

        $warehouse->fill($request->only([
            'name',
            'description',
        ]));
        $warehouse->save();

        return $this->show($warehouse->id);
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        return $warehouse->delete();
    }
}
