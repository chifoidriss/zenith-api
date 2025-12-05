<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLog;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index($type)
    {
        $date = request()->date;
        $sens = request()->sens;
        $payment_method = request()->payment_method;

        if($date == 'today') {
            $form_date = date('Y-m-d');
            $to_date = date('Y-m-d');
        } else if($date == 'yesterday') {
            $form_date = now()->yesterday()->format('Y-m-d');
            $to_date = now()->yesterday();
        } else if($date == 'week') {
            $form_date = now()->subDays(7)->format('Y-m-d');
            $to_date = date('Y-m-d');
        } else if($date == 'week2') {
            $form_date = now()->subDays(15)->format('Y-m-d');
            $to_date = date('Y-m-d');
        } else if($date == 'month') {
            $form_date = now()->subMonth()->format('Y-m-d');
            $to_date = date('Y-m-d');
        } else if($date == 'trim') {
            $form_date = now()->subMonths(3)->format('Y-m-d');
            $to_date = date('Y-m-d');
        } else if($date == 'sem') {
            $form_date = now()->subMonths(6)->format('Y-m-d');
            $to_date = date('Y-m-d');
        } else if($date == 'year') {
            $form_date = now()->subYear()->format('Y-m-d');
            $to_date = date('Y-m-d');
        } else if($date == 'custom') {
            $form_date = request()->form_date;
            $to_date = request()->to_date;
        } else {
            $form_date = date('Y-m-d');
            $to_date = date('Y-m-d');
        }

        $payments = Payment::with([
            'partner',
            'invoice',
            'paymentMethod'
        ])->whereBetween('payment_date', [$form_date, $to_date]);

        if($payment_method) {
            $payments = $payments->where('payment_method_id', $payment_method);
        }
        if($sens) {
            $payments = $payments->where('type', $sens);
        }

        if($type != 'entries') {
            $source = invoiceSource($type);
            $payments = $payments->whereHas('invoice', function($query) use($source) {
                $query->where('source', $source);
            });
        }

        return $payments->orderBy('created_at', 'DESC')->paginate(10);
    }

    public function show($type, $id)
    {
        return Payment::with(['partner', 'invoice', 'paymentMethod'])->findOrFail($id);
    }

    public function store(Request $request, $type)
    {
        $request->validate([
            'partner_id'=>'nullable|exists:partners,id',
            'payment_method_id'=>'required|exists:payment_methods,id',
            'devise_id'=>'required|exists:devises,id',
            'invoice_id'=>'required|exists:invoices,id',
            'payment_date'=>'required|date',
            'amount'=>'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            $source = invoiceSource($type);

            $payment = Payment::firstOrNew([
                'id' => $request->id
            ]);

            $payment->fill($request->only([
                'partner_id',
                'payment_method_id',
                'devise_id',
                'invoice_id',
                'payment_date',
                'amount',
            ]));

            $invoice = Invoice::where([
                'id' => $request->invoice_id
            ])->firstOrFail();

            if(!$payment->type) {
                if ($invoice->type == 'INVOICE') {
                    if($invoice->source == 'PURCHASE' || $invoice->source == 'SALARY') {
                        $payment->type = 'OUT';
                    } else {
                        $payment->type = 'IN';
                    }
                } elseif ($invoice->type == 'REFUND') {
                    if($invoice->source == 'PURCHASE' || $invoice->source == 'SALARY') {
                        $payment->type = 'IN';
                    } else {
                        $payment->type = 'OUT';
                    }
                }
            }

            if(!$payment->reference) {
                $last = Payment::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
                ->where('type', $payment->type)->first();
                // ->orderBy('id', 'DESC')->first();
                $ref = 'P'.$payment->type.'/';
                $ref .= str_pad($last?$last->number+1:1, 4, '0', STR_PAD_LEFT);
                $payment->reference = $ref;
            }

            $payment->status = true;
            $payment->save();

            PaymentLog::create([
                'user_id' => auth()->id(),
                'payment_id' => $payment->id,
                'action' => $request->id ? 'UPDATE' : 'CREATE',
                'data' => json_encode($payment),
                'document' => $payment->reference,
                'request' => json_encode($request->all()),
            ]);

            # Accounting entries
            $this->entry($payment);

            # All is good
            DB::commit();

            return $this->show($type, $payment->id);
        } catch (Throwable $ex) {
            DB::rollBack();

            return response([
                'error' => 'Erreur interne du serveur',
                'message' => $ex,
            ], 500);

            throw $ex;
        }
    }

    public function destroy($type, $id)
    {
        $payment = Payment::findOrFail($id);

        PaymentLog::create([
            'user_id' => auth()->id(),
            // 'payment_id' => $payment->id,
            'action' => 'DELETE',
            'document' => $payment->reference,
            'data' => json_encode($payment),
        ]);

        return $payment->delete();
    }

    public function cancel($type, $id)
    {
        $oldPayment = Payment::findOrFail($id);

        DB::beginTransaction();

        try {
            $payment = $oldPayment->replicate();
            $payment->type = $payment->type == 'IN' ? 'OUT' : 'IN';

            $last = Payment::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
            ->where('type', $payment->type)->first();
            $ref = 'P'.$payment->type.'/';
            $ref .= str_pad($last?$last->number+1:1, 4, '0', STR_PAD_LEFT);
            $payment->reference = $ref;
            $payment->save();

            PaymentLog::create([
                'user_id' => auth()->id(),
                'payment_id' => $payment->id,
                'action' => 'CREATE',
                'document' => $payment->reference,
                'data' => json_encode($payment),
            ]);

            # Accounting entries
            $this->entry($payment);

            # All is good
            DB::commit();

            return $this->show($type, $payment->id);
        } catch (Throwable $ex) {
            DB::rollBack();

            return response([
                'error' => 'Erreur interne du serveur',
                'message' => $ex,
            ], 500);

            throw $ex;
        }
    }

    private function entry($payment) {
        $label = ($payment->type == 'IN'?'Encaissement':'Décaissement').' '.$payment->paymentMethod->name.' ';
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;

        $p2 = AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => $payment->partner->default_account_id,
            'partner_id' => $payment->partner_id,
            'payment_id' => $payment->id,
            'label' => $label,
            $payment->type == 'IN'?'credit':'debit' => $payment->amount,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => $payment->paymentMethod->cash_account_id,
            'partner_id' => $payment->partner_id,
            'payment_id' => $payment->id,
            'label' => $label,
            $payment->type == 'IN'?'debit':'credit' => $payment->amount,
        ]);

        if ($payment->invoice->source == 'SALARY') {
            if ($payment->invoice->salary) {
                # Paiement des impots
                $label = 'Paiement des impots';
                $total = $payment->invoice->salary->total_tax;
                $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => 536,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'debit' => $total,
                ]);
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => $payment->paymentMethod->cash_account_id,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'credit' => $total,
                ]);

                # Paiement des CNPS
                $label = 'Paiement des CNPS';
                $total = $payment->invoice->salary->total_cnps;
                $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => 504,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'debit' => $total,
                ]);
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => $payment->paymentMethod->cash_account_id,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'credit' => $total,
                ]);

                # Paiement des taxes communale
                $label = 'Paiement des taxes communale';
                $total = $payment->invoice->salary->tax_municipal_total;
                $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => 515,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'debit' => $total,
                ]);
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => $payment->paymentMethod->cash_account_id,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'credit' => $total,
                ]);

                # Paiement des retenues syndicales
                $label = 'Paiement des retenues syndicales';
                $total = $payment->invoice->salary->syndical_total;
                $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => 506,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'debit' => $total,
                ]);
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => $payment->paymentMethod->cash_account_id,
                    'partner_id' => $payment->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'credit' => $total,
                ]);
            } else if($payment->invoice->loan) {
                $p2->chart_account_id = 480;
                $p2->save();

                $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                // $label = 'Pret '.$payment->paymentMethod->name;
                $label = 'Pret sur salaire';
                $loan = $payment->invoice->loan;
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => $loan->partner->default_account_id,
                    'partner_id' => $loan->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'debit' => $loan->amount,
                ]);
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => 480,
                    'partner_id' => $loan->partner_id,
                    'payment_id' => $payment->id,
                    'label' => $label,
                    'credit' => $loan->amount,
                ]);
            }
        }
    }
}
