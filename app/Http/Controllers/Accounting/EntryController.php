<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Invoice;
use App\Models\InvoiceLog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class EntryController extends Controller
{
    public function index()
    {
        $date = request()->date;
        $source = request()->source;

        if($date == 'today') {
            $form_date = now()->yesterday();
            $to_date = now();
        } else if($date == 'yesterday') {
            $form_date = now()->subDays(2);
            $to_date = now()->yesterday();
        } else if($date == 'week') {
            $form_date = now()->subDays(7);
            $to_date = now();
        } else if($date == 'month') {
            $form_date = now()->subMonth();
            $to_date = now();
        } else if($date == 'year') {
            $form_date = now()->subYear();
            $to_date = now();
        } else if($date == 'custom') {
            $form_date = request()->form_date;
            $to_date = request()->to_date;
        } else {
            $form_date = now();
            $to_date = now()->tomorrow();
        }

        $entries = AccountingEntry::with([
            'chartAccount',
            // 'salary',
            // 'partner',
            // 'fiscalYear',
            // 'invoice',
            // 'journal',
            // 'article',
        ])->whereBetween('created_at', [$form_date, $to_date]);

        if (in_array($source, ['hosting', 'bar', 'restaurant', 'purchase'])) {
            $entries = $entries->whereHas('invoice', function($query) use ($source) {
                $query->where('source', Str::upper($source));
            });
        } elseif($source == 'stock') {
            $entries = $entries->whereHas('transfer');
        } elseif($source == 'payment') {
            $entries = $entries->whereHas('payment');
        } elseif($source == 'salary') {
            $entries = $entries->whereHas('salary');
        }

        // return $entries->orderBy('document_group', 'DESC')->orderBy('debit', 'DESC')->paginate(20);
        return $entries->orderBy('document_group', 'DESC')->orderBy('debit', 'DESC')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'devise_id'=>'required|exists:devises,id',
            'partner_id'=>'nullable|exists:partners,id',
            'reference'=>'required|unique:invoices,reference',
            // 'document' => 'nullable|file|size:1048576',
            'document' => 'nullable|file',
        ]);

        DB::beginTransaction();

        // try {
            $invoice = Invoice::firstOrNew([
                'id' => $request->input('id')
            ]);

            $invoice->fill($request->only([
                'devise_id',
                'reference',
                'partner_id',
                'billing_date',
            ]));
            $invoice->fill([
                'source' => 'OTHER',
                'type' => 'INVOICE',
                'subtotal' => 0,
                'total' => 0,
                'status' => true,
            ]);

            if($request->hasFile('document')) {
                $invoice->document = $request->file('document')->store('documents', 'public');
            }

            $invoice->save();

            InvoiceLog::create([
                'user_id' => auth()->id(),
                'invoice_id' => $invoice->id,
                'action' => 'CREATE',
                'document' => $invoice->reference,
                'data' => json_encode($invoice),
                'request' => json_encode($request->all()),
            ]);

            $group = (AccountingEntry::max("document_group") ?? 1) + 1;

            if ($request->lines) {
                foreach(json_decode($request->lines, true) as $line) {
                    AccountingEntry::create([
                        'document_group' => $group,
                        'fiscal_year_id' => 1,
                        'journal_id' => 1,
                        'chart_account_id' => $line["chart_account_id"],
                        'partner_id' => $invoice->partner_id,
                        'invoice_id' => $invoice->id,
                        'label' => $line["label"],
                        'credit' => $line["credit"],
                        'debit' => $line["debit"],
                    ]);
                }
            }

            # All is good
            DB::commit();

            return $this->show($invoice->id);
        // } catch (Throwable $ex) {
        //     DB::rollBack();

        //     return response([
        //         'error' => 'Erreur interne du serveur',
        //         'message' => $ex,
        //     ], 500);

        //     throw $ex;
        // }

    }

    public function show($id)
    {
        return AccountingEntry::with([
            'partner',
            'chartAccount',
            'fiscalYear',
            'invoice',
            'journal',
            'article',
        ])->findOrFail($id);
    }

    public function destroy($id)
    {
        $chart_account = AccountingEntry::findOrFail($id);
        return $chart_account->delete();
    }
}
