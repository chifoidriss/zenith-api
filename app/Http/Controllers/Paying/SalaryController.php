<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\AccountingEntry;
use App\Models\Advance;
use App\Models\BonusSalary;
use App\Models\Contract;
use App\Models\Devise;
use App\Models\IndemnitySalary;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\Salary;
use App\Models\Society;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SalaryController extends Controller
{
    public function index()
    {
        $month = request()->current_month;
        $start = Carbon::parse($month.'-01');
        $end = Carbon::parse($month.'-01')->lastOfMonth();
        $data = Contract::with('partner', 'post', 'contractType')
        ->where('start_date', '<=', $start)
        ->where(function ($query) use($end) {
            $query->where('end_date', '>=', $end)
            ->orWhereNull('end_date');
        })->withWhereHas('pay', function ($query) use($start, $end) {
            $query->with('invoice')
            ->where('start_date', $start)
            ->where('end_date', $end);
        }, '>=', 0)->paginate(20);

        return $data;
    }

    public function show($id)
    {
        return Salary::with('partner', 'invoice', 'contract.post', 'bonuses', 'indemnities')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'current_month' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $start = Carbon::parse($request->current_month.'-01');
            $end = Carbon::parse($request->current_month.'-01')->lastOfMonth();

            if ($end > now()) {
                return response()->json([
                    'message' => 'Opération impossible.',
                    'errors' => [
                        'current_month' => ['Date incorrecte.']
                    ]
                ], 422);
            }

            $contract = Contract::findOrFail($request->contract_id);

            $salary = Salary::firstOrNew([
                'start_date' => $start,
                'end_date' => $end,
                'contract_id' => $contract->id,
                'partner_id' => $contract->partner_id,
            ]);

            $leave_total = Leave::where('payed', true)->whereBetween('start_date', [$start, $end])->sum('amount');
            $absence_total = Absence::where('justified', false)->whereBetween('start_date', [$start, $end])->sum('amount');
            $advance_total = Advance::where('contract_id', $contract->id)->whereBetween('start_date', [$start, $end])->sum('amount');
            $loan_total = LoanDetail::whereBetween('pay_date', [$start, $end])
            ->whereHas('loan', function ($query) use($contract) {
                $query->where('contract_id', $contract->id);
            })->sum('amount');

            $bonus_total = $contract->bonuses->sum('pivot.value');
            $indemnity_total = $contract->indemnities->sum('pivot.value');
            $salary_total = $contract->salary;

            $mrb = $salary_total + $bonus_total + $indemnity_total + $leave_total;
            $assiette = $mrb * 0.658;
            if($salary_total < 62000) {
                $tax_irpp_total = 0;
            } else {
                if($assiette < 166000) {
                    $tax_irpp_total = $assiette * 0.1;
                } else if($assiette > 166000 && $assiette <= 250000) {
                    $tax_irpp_total = $assiette * 0.15;
                } else if($assiette > 250000 && $assiette <= 416666) {
                    $tax_irpp_total = $assiette * 0.25;
                }
            }

            $tax_cac_total = $tax_irpp_total * 0.1;
            $tax_cfc_total = $mrb * 0.01;

            if($mrb <= 50000) {
                $tax_crtv_total = 0;
            } else if($mrb > 50000 && $mrb <= 100000) {
                $tax_crtv_total = 750;
            } else if($mrb > 100000 && $mrb <= 200000) {
                $tax_crtv_total = 1950;
            } else if($mrb > 200000 && $mrb <= 300000) {
                $tax_crtv_total = 3250;
            } else if($mrb > 300000 && $mrb <= 400000) {
                $tax_crtv_total = 4550;
            } else if($mrb > 400000 && $mrb <= 500000) {
                $tax_crtv_total = 5850;
            } else if($mrb > 500000 && $mrb <= 600000) {
                $tax_crtv_total = 7150;
            }

            if($salary_total < 62000) {
                $tax_municipal_total = 0;
            } else if($salary_total >= 62000 && $salary_total <= 75000) {
                $tax_municipal_total = 3000;
            } else if($salary_total > 75000 && $salary_total <= 100000) {
                $tax_municipal_total = 6000;
            } else if($salary_total > 100000 && $salary_total <= 125000) {
                $tax_municipal_total = 9000;
            } else if($salary_total > 125000 && $salary_total <= 150000) {
                $tax_municipal_total = 12000;
            } else if($salary_total > 150000 && $salary_total <= 200000) {
                $tax_municipal_total = 15000;
            } else if($salary_total > 200000 && $salary_total <= 250000) {
                $tax_municipal_total = 18000;
            } else if($salary_total > 250000 && $salary_total <= 300000) {
                $tax_municipal_total = 24000;
            } else if($salary_total > 300000 && $salary_total <= 500000) {
                $tax_municipal_total = 27000;
            } else if($salary_total > 500000) {
                $tax_municipal_total = 30000;
            }

            $tax_municipal_total = $tax_municipal_total / 12;

            $pvid_total = 0.042*$mrb;
            $syndical_total = 0.01*$mrb;

            $total = $mrb - ($tax_irpp_total + $tax_cac_total + $tax_cfc_total + $tax_crtv_total)
            - ($tax_municipal_total + $pvid_total + $syndical_total + $loan_total + $advance_total + $absence_total);

            if(!$salary->invoice_id) {
                $last = Invoice::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
                        ->where(['type' => 'INVOICE', 'source' => 'SALARY'])->first();

                $ref = 'FACSAL/';
                $ref .= str_pad($last?$last->number+1:1, 4, '0', STR_PAD_LEFT);

                $devise = Devise::first();

                $invoice = new Invoice([
                    'partner_id' => $contract->partner_id,
                    'devise_id' => $devise->id,
                    'type' => 'INVOICE',
                    'source' => 'SALARY',
                    'reference' => $ref,
                    'billing_date' => now(),
                    'due_date' => now()->addDay(),
                    'status' => true,
                ]);
            } else {
                $invoice = Invoice::findOrFail($salary->invoice_id);
            }

            $invoice->total = $total;
            $invoice->save();

            $salary->invoice_id = $invoice->id;
            $salary->salary_total = $salary_total;
            $salary->bonus_total = $bonus_total;
            $salary->indemnity_total = $indemnity_total;
            $salary->leave_total = $leave_total;
            $salary->absence_total = $absence_total;
            $salary->tax_irpp_total = $tax_irpp_total;
            $salary->tax_cac_total = $tax_cac_total;
            $salary->tax_cfc_total = $tax_cfc_total;
            $salary->tax_crtv_total = $tax_crtv_total;
            $salary->tax_municipal_total = $tax_municipal_total;
            $salary->pvid_total = $pvid_total;
            $salary->syndical_total = $syndical_total;
            $salary->salary_advance = $advance_total;
            $salary->salary_loan = $loan_total;
            $salary->save();

            foreach ($contract->bonuses as $item) {
                $bonus = BonusSalary::firstOrNew([
                    'salary_id' => $salary->id,
                    'bonus_id' => $item->id,
                ]);
                $bonus->value = $item->pivot->value;
                $bonus->save();
            }

            foreach ($contract->indemnities as $item) {
                $indemnity = IndemnitySalary::firstOrNew([
                    'salary_id' => $salary->id,
                    'indemnity_id' => $item->id,
                ]);
                $indemnity->value = $item->pivot->value;
                $indemnity->save();
            }

            $this->entry($salary);

            # All is good
            DB::commit();

            // return $salary;
            return $this->show($salary->id);
        } catch (Throwable $ex) {
            DB::rollBack();

            return response([
                'error' => 'Erreur interne du serveur',
                'message' => $ex,
            ], 500);

            throw $ex;
        }
    }

    public function destroy($id)
    {
        $salary = Salary::where('id', $id)->whereDoesntHave('invoice.payments')->firstOrFail();
        Invoice::findOrFail($salary->invoice_id)->delete();
        BonusSalary::where('salary_id', $salary->id)->delete();
        IndemnitySalary::where('salary_id', $salary->id)->delete();

        return $salary->delete();
    }

    private function entry($salary) {
        $label = "Salaire de base";
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;

        # Salaire de base
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 873,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->salary_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => $salary->partner->default_account_id,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->salary_total,
        ]);

        # Primes
        foreach ($salary->bonuses as $item) {
            $label = $item->name;
            $group = (AccountingEntry::max("document_group") ?? 1) + 1;
            AccountingEntry::create([
                'document_group' => $group,
                'fiscal_year_id' => 1,
                'journal_id' => 1,
                'chart_account_id' => 874,
                'partner_id' => $salary->partner_id,
                'salary_id' => $salary->id,
                'label' => $label,
                'debit' => $item->pivot->value,
            ]);
            AccountingEntry::create([
                'document_group' => $group,
                'fiscal_year_id' => 1,
                'journal_id' => 1,
                'chart_account_id' => $salary->partner->default_account_id,
                'partner_id' => $salary->partner_id,
                'salary_id' => $salary->id,
                'label' => $label,
                'credit' => $item->pivot->value,
            ]);
        }

        # Indemnités
        foreach ($salary->indemnities as $item) {
            $label = $item->name;
            $group = (AccountingEntry::max("document_group") ?? 1) + 1;
            AccountingEntry::create([
                'document_group' => $group,
                'fiscal_year_id' => 1,
                'journal_id' => 1,
                'chart_account_id' => 889,
                'partner_id' => $salary->partner_id,
                'salary_id' => $salary->id,
                'label' => $label,
                'debit' => $item->pivot->value,
            ]);
            AccountingEntry::create([
                'document_group' => $group,
                'fiscal_year_id' => 1,
                'journal_id' => 1,
                'chart_account_id' => $salary->partner->default_account_id,
                'partner_id' => $salary->partner_id,
                'salary_id' => $salary->id,
                'label' => $label,
                'credit' => $item->pivot->value,
            ]);
        }

        # Impôts sur salaires IRPP
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        $label = "Impôts sur salaires IRPP";
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 841,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->tax_irpp_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 536,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->tax_irpp_total,
        ]);

        # Impôts sur salaires CAC
        $label = "Impôts sur salaires CAC";
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 841,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->tax_cac_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 536,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->tax_cac_total,
        ]);

        # Impôts sur salaires CFC
        $label = "Impôts sur salaires CFC";
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 841,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->tax_cfc_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 536,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->tax_cfc_total,
        ]);

        # Impôts sur salaires CRTV
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        $label = "Impôts sur salaires CRTV";
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 841,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->tax_crtv_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 536,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->tax_crtv_total,
        ]);

        # PVID Salaire
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        $label = "PVID Salaire";
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 894,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->pvid_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 504,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->pvid_total,
        ]);

        # PVID Employeur
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        $label = "PVID Employeur";
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 894,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->pvid_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 504,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->pvid_total,
        ]);

        $mrb = $salary->salary_total + $salary->bonus_total + $salary->indemnity_total;

        # Prestation Familial
        $label = "Prestation Familial";
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 894,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $mrb * 0.07,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 504,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $mrb * 0.07,
        ]);

        # RP
        $label = "RP";
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 894,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $mrb * 0.0175,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 504,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $mrb * 0.0175,
        ]);

        # Taxes communales
        $label = "Taxes communales";
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 841,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->tax_municipal_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 515,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->tax_municipal_total,
        ]);

        # Retenues Syndicales
        $label = "Retenues Syndicales";
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => $salary->partner->default_account_id,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'debit' => $salary->syndical_total,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => 506,
            'partner_id' => $salary->partner_id,
            'salary_id' => $salary->id,
            'label' => $label,
            'credit' => $salary->syndical_total,
        ]);
    }
}
