<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\ContractType;
use Illuminate\Http\Request;

class ContractTypeController extends Controller
{
    public function index()
    {
        return ContractType::paginate(100);
    }

    public function show($id)
    {
        return ContractType::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $contractType = ContractType::firstOrNew([
            'id' => $request->input('id')
        ]);

        $contractType->fill($data);
        $contractType->save();

        return $this->show($contractType->id);
    }

    public function destroy($id)
    {
        $contractType = ContractType::findOrFail($id);
        return $contractType->delete();
    }
}
