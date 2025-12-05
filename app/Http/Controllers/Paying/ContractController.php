<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\BonusContract;
use App\Models\Contract;
use App\Models\ContractIndemnity;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index()
    {
        return Contract::with([
            'partner',
            'contractType',
            'post',
            'bonuses',
            'indemnities',
        ])->paginate(100);
    }

    public function show($id)
    {
        return Contract::with([
            'partner',
            'contractType',
            'post',
            'bonuses',
            'indemnities',
        ])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'contract_type_id' => 'required|exists:contract_types,id',
            'post_id' => 'required|exists:posts,id',
            'salary' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'observation' => 'nullable|string',
        ]);

        $request->validate([
            'bonuses' => 'nullable|array',
            'indemnities' => 'nullable|array',
        ]);

        $contract = Contract::firstOrNew([
            'id' => $request->input('id')
        ]);

        $contract->fill($data);
        $contract->save();

        foreach ($request->bonus as $key => $value) {
            $bonus = BonusContract::firstOrNew([
                'contract_id' => $contract->id,
                'bonus_id' => $request->bonuses[$key],
            ]);
            $bonus->value = $value;
            $bonus->save();
        }

        foreach ($request->indemnity as $key => $value) {
            $indemnity = ContractIndemnity::firstOrNew([
                'contract_id' => $contract->id,
                'indemnity_id' => $request->indemnities[$key],
            ]);
            $indemnity->value = $value;
            $indemnity->save();
        }

        return $this->show($contract->id);
    }

    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);
        return $contract->delete();
    }

    public function filter()
    {
        $q = request()->q;

        return Contract::with([
            'partner',
        ])->whereHas('partners', function($query) use($q) {
            $query->where('first_name', 'LIKE', "%$q%")
            ->orWhere('last_name', 'LIKE', "%$q%");
        })->get();
    }
}
