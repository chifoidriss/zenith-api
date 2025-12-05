<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Contract;
use App\Models\Devise;
use App\Models\Invoice;
use App\Models\Loan;
use App\Models\LoanDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        return Loan::with('partner', 'invoice')->paginate(20);
    }

    public function show($id)
    {
        return Loan::with('partner', 'invoice')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $contract = Contract::findOrFail($request->contract_id);

        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'reason' => 'nullable|string',
            // 'start_date' => 'required|date',
            'amount' => 'required|numeric|min:1|max:'.($contract->salary*3),
            'months' => 'required|integer|min:1|max:3',
        ]);

        $start_date = Carbon::parse($request->start_date);
        if ($start_date < now()->firstOfMonth()) {
            return response()->json([
                'message' => 'Opération impossible.',
                'errors' => [
                    'start_date' => ['Date incorrecte.']
                ]
            ], 422);
        }

        $loan = Loan::firstOrNew([
            'id' => $request->input('id')
        ]);

        $loan->fill($data);
        $loan->partner_id = $contract->partner_id;
        $loan->start_date = $start_date;

        if(!$loan->invoice_id) {
            $last = Invoice::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
                    ->where(['type' => 'INVOICE', 'source' => 'SALARY'])->first();

            $ref = 'FACSAL/';
            $ref .= str_pad($last?$last->number+1:1, 4, '0', STR_PAD_LEFT);

            $devise = Devise::first();

            $invoice = new Invoice([
                'partner_id' => $loan->partner_id,
                'devise_id' => $devise->id,
                'type' => 'INVOICE',
                'source' => 'SALARY',
                'reference' => $ref,
                'billing_date' => now(),
                'due_date' => now()->addDay(),
                'status' => true,
            ]);
        } else {
            $invoice = Invoice::findOrFail($loan->invoice_id);
        }

        $invoice->total = $loan->amount;
        $invoice->save();

        $loan->invoice_id = $invoice->id;
        $loan->save();

        for ($i=0; $i < $request->months; $i++) {
            LoanDetail::create([
                'loan_id' => $loan->id,
                'pay_date' => now()->addMonths($i),
                'amount' => $loan->amount/$request->months
            ]);
        }

        // $this->entry($loan);

        return $this->show($loan->id);
    }

    public function destroy($id)
    {
        $loan = Loan::where('id', $id)->whereDoesntHave('invoice.payments')->firstOrFail();
        Invoice::findOrFail($loan->invoice_id)->delete();
        return $loan->delete();
    }

    private function entry($loan)
    {
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        $label = 'Pret';
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => $loan->partner->default_account_id,
            'partner_id' => $loan->partner_id,
            // 'payment_id' => $payment->id,
            'label' => $label,
            'debit' => $loan->amount,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 480,
            'partner_id' => $loan->partner_id,
            // 'payment_id' => $payment->id,
            'label' => $label,
            'credit' => $loan->amount,
        ]);
    }
}
