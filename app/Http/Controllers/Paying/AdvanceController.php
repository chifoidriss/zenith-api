<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Contract;
use App\Models\Devise;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdvanceController extends Controller
{
    public function index()
    {
        return Advance::with('partner', 'invoice')->paginate(20);
    }

    public function show($id)
    {
        return Advance::with('partner', 'invoice')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $contract = Contract::findOrFail($request->contract_id);

        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'reason' => 'nullable|string',
            'amount' => 'required|numeric|min:1|max:'.($contract->salary),
            // 'start_date' => 'required|date',
        ]);

        $advance = Advance::firstOrNew([
            'id' => $request->input('id')
        ]);

        $advance->fill($data);
        $advance->partner_id = $contract->partner_id;
        $advance->start_date = now();

        if(!$advance->invoice_id) {
            $last = Invoice::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
                    ->where(['type' => 'INVOICE', 'source' => 'SALARY'])->first();

            $ref = 'FACSAL/';
            $ref .= str_pad($last?$last->number+1:1, 4, '0', STR_PAD_LEFT);

            $devise = Devise::first();

            $invoice = new Invoice([
                'partner_id' => $advance->partner_id,
                'devise_id' => $devise->id,
                'type' => 'INVOICE',
                'source' => 'SALARY',
                'reference' => $ref,
                'billing_date' => now(),
                'due_date' => now()->addDay(),
                'status' => true,
            ]);
        } else {
            $invoice = Invoice::findOrFail($advance->invoice_id);
        }

        $invoice->total = $advance->amount;
        $invoice->save();

        $advance->invoice_id = $invoice->id;
        $advance->save();

        return $this->show($advance->id);
    }

    public function destroy($id)
    {
        $advance = Advance::where('id', $id)->whereDoesntHave('invoice.payments')->firstOrFail();
        Invoice::findOrFail($advance->invoice_id)->delete();
        return $advance->delete();
    }
}
