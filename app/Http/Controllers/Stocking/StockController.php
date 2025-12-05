<?php

namespace App\Http\Controllers\Stocking;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Unit;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $warehouse = request()->warehouse;

        $articles = Stock::with(['article.category', 'unit'])
        ->whereHas('warehouse', function($query) use($warehouse) {
            $query->where('short_name', $warehouse);
        });

        return $articles->paginate(100);
    }

    public function show($id)
    {
        return Stock::with(['article.category', 'unit'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'article_id' => 'required|exists:articles,id',
            'unit_id' => 'required|exists:units,id',
            'qty' => 'required|numeric',
            'qty_min' => 'nullable|numeric',
            'price' => 'nullable|numeric',
        ]);

        $stock = Stock::firstOrNew([
            'id' => $request->input('id')
        ]);

        $unit = Unit::findOrFail($request->unit_id);

        if($unit->unity > 1) {
            $qty = $request->qty*$unit->unity;
            $qty_min = $request->qty_min*$unit->unity;
        } else {
            $qty = $request->qty;
            $qty_min = $request->qty_min;
        }

        $stock->fill($data);
        $stock->qty = $qty;
        $stock->qty_min = $qty_min;
        $stock->save();

        return $this->show($stock->id);
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        return $stock->delete();
    }
}
