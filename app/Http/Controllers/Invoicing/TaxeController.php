<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Taxe;
use Illuminate\Http\Request;

class TaxeController extends Controller
{
    public function index()
    {
        return Taxe::with('chartAccount')->paginate(100);
    }

    public function show($id)
    {
        return Taxe::with('chartAccount')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string',
            'label'=>'nullable|string',
            'type'=>'required|string|in:IN,OUT',
            'calcul'=>'required|string|in:FIXED,PERCENT',
            'value'=>'required|numeric',
            'status'=>'required|boolean',
            'chart_account_id'=>'nullable|exists:chart_accounts,id',
        ]);

        $taxe = Taxe::firstOrNew([
            'id' => $request->input('id')
        ]);

        $taxe->fill($data);
        $taxe->save();

        return $this->show($taxe->id);
    }

    public function destroy($id)
    {
        $taxe = Taxe::findOrFail($id);
        return $taxe->delete();
    }
}
