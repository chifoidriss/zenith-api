<?php

namespace App\Http\Controllers\Invoicing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Unit;
use App\Models\Society;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Partner;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\ChartAccount;
use App\Models\InvoiceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvoiceController extends Controller
{
    public function statistics()
    {
        // $sources = ['SALE', 'PURCHASE'];
        $types = ['INVOICE', 'REFUND'];
        $result = [];
        $stats = [];

        $date = request()->date;
        $source = invoiceSource(request()->source);
        $today = date('Y-m-d');

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
            $form_date = request()->form_date ?? date('Y-m-d');
            $to_date = request()->to_date ?? date('Y-m-d');
        } else {
            $form_date = date('Y-m-d');
            $to_date = date('Y-m-d');
        }

        $sql = 'SUM(total) as total, SUM(subtotal) as subtotal, count(id) as nb';

        foreach ($types as $type) {
            $due = Invoice::selectRaw($sql)
            ->whereBetween('billing_date', [$form_date, $to_date])
            ->whereDate('due_date', '<=', $today)
            ->where([
                'status' => true,
                'source' => $source,
                'type' => $type,
            ])->where(function ($query) {
                $query->whereHas('payments',function($query){
                    $query->select('invoice_id')
                    ->havingRaw('invoices.total>SUM(IF((type="IN"), amount, amount*-1))')
                    ->groupBy('invoice_id');
               })->orDoesntHave('payments');
            })->first();

            $unpaid = Invoice::selectRaw($sql)
            ->whereBetween('billing_date', [$form_date, $to_date])
            ->where([
                'status' => true,
                'source' => $source,
                'type' => $type,
            ])->where(function ($query) {
                $query->whereHas('payments',function($query){
                    $query->select('invoice_id')
                    ->havingRaw('invoices.total>SUM(IF((type="IN"), amount, amount*-1))')
                    ->groupBy('invoice_id');
               })->orDoesntHave('payments');
            })->first();

            $paid = Invoice::selectRaw($sql)
            ->whereBetween('billing_date', [$form_date, $to_date])
            ->where([
                'status' => true,
                'source' => $source,
                'type' => $type,
            ])->whereHas('payments',function($query){
                $query->select('invoice_id')
                ->havingRaw('invoices.total=SUM(IF((type="IN"), amount, amount*-1))')
                ->groupBy('invoice_id');
            })->first();

            $pending = Invoice::selectRaw($sql)
            ->whereBetween('billing_date', [$form_date, $to_date])
            ->where([
                'status' => false,
                'source' => $source,
                'type' => $type,
            ])->first();

            $valid = Invoice::selectRaw($sql)
            ->whereBetween('billing_date', [$form_date, $to_date])
            ->where([
                'status' => true,
                'source' => $source,
                'type' => $type,
            ])->first();

            array_push($result, compact([
                'source',
                'type',
                'unpaid',
                'paid',
                'pending',
                'valid',
                'due',
            ]));
        }

        $labels = [];
        $labelsName = [];
        $year = Carbon::createFromFormat('Y-m-d', $form_date)->year;
        $f_date = Carbon::createFromFormat('Y-m-d', $form_date)->dayOfYear;
        $t_date = Carbon::createFromFormat('Y-m-d', $to_date)->dayOfYear;
        for ($i = $f_date; $i <= $t_date; $i++) {
            array_push($labels, $i);
            $name = Carbon::now()->dayOfYear($i);
            array_push($labelsName, ($name->day<10?0:'').$name->day.'/'.($name->month<10?0:'').$name->month);
        }

        $d = Invoice::selectRaw("SUM(total) as count")
        ->selectRaw("COUNT(id) as nb")
        ->selectRaw("DAYOFYEAR(billing_date) as name")
        ->whereBetween('billing_date', [$form_date, $to_date])
        ->where(['source' => $source])
        ->groupByRaw("name")
        ->get();

        $values = [];
        $invoices = [];
        foreach ($labels as $label) {
            $v = $d->where('name', $label)->first();
            if($v) {
                array_push($values, $v->count);
                array_push($invoices, $v);
            } else {
                array_push($values, 0);
            }
        }

        $vals = [];
        $labs = [];
        $labsName = [];
        foreach($values as $i => $val) {
            if($val != 0) {
                array_push($vals, $val);
                array_push($labs, $labels[$i]);
                array_push($labsName, $labelsName[$i]);
            }
        }
        $values = $vals;
        $labels = $labs;
        $labelsName = $labsName;

        $stats = [
            'year' => $year,
            'labels' => $labels,
            'labelsName' => $labelsName,
            'values' => $values,
            'invoices' => $invoices
        ];

        return ['data' => $result, 'stats'=> $stats];
    }

    public function followup($type)
    {
        $follow = request()->follow;
        $date = request()->date;
        $source = request()->source;

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

        // $partners = Partner::where(['type' => partnerType($type)]);
        $partners = Partner::where(['type' => partnerType($source)]);

        if($follow == 'due') {
            $partners =  $partners->withWhereHas('dueInvoices', function ($query) use($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            });
        } elseif($follow == 'unpaid') {
            $partners =  $partners->withWhereHas('unpaidInvoices', function ($query) use($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            });
        } else if($follow == 'paid') {
            $partners =  $partners->whereDoesntHave('unpaidInvoices', function ($query) use($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            })
            ->withWhereHas('paidInvoices', function ($query) use($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            }, '>=', 1);
        } else if($follow == 'best_by_total') {
            $partners =  $partners->withCount(['paidInvoices' => function ($query) use ($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            }])
            ->withSum(['paidInvoices' => function($query) use($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            }], 'total')
            ->whereDoesntHave('unpaidInvoices', function ($query) use($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            })
            ->withWhereHas('paidInvoices', function ($query) use($form_date, $to_date) {
                $query->whereBetween('billing_date', [$form_date, $to_date]);
            // }, '>=', 1)->orderBy('paid_invoices_count', 'DESC');
            }, '>=', 1)->orderBy('paid_invoices_sum_total', 'DESC');
        }

        return $partners->paginate(20);
    }

    public function index($invoice_type, $type)
    {
        // $source = $type == 'clients'?'SALE':'PURCHASE';
        $source = $source = invoiceSource($type);
        $invoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';

        $invoices = Invoice::with(['partner']);
        if($type != 'entries') {
            $invoices = $invoices->where([
                'source' => $source,
                'type' => $invoiceType
            ]);
        }

        $order = request()->order;
        $form_date = request()->form_date;
        $to_date = request()->to_date;
        $invoice_status = request()->invoice_status;
        $payment_status = request()->payment_status;
        $by = request()->by;

        if($invoice_status == 'active') {
            $invoices = $invoices->where('status', true);
        } elseif($invoice_status == 'pending') {
            $invoices = $invoices->where('status', false);
        }

        if($payment_status == 'unpaid') {
            $invoices = $invoices->where(function ($query) {
                $query->whereHas('payments', function($query){
                    $query->select('invoice_id')
                    ->havingRaw('invoices.total>SUM(IF((type="IN"), amount, amount*-1))')
                    ->groupBy('invoice_id');
               })->orDoesntHave('payments');
            });
        } elseif($payment_status == 'paid') {
            $invoices = $invoices->whereHas('payments', function($query){
                $query->select('invoice_id')
                ->havingRaw('invoices.total<=SUM(IF((type="IN"), amount, amount*-1))')
                ->groupBy('invoice_id');
           });
        }

        if($form_date) {
            $invoices = $invoices->whereDate('billing_date', '>=', $form_date);
        }
        if($to_date) {
            $invoices = $invoices->whereDate('billing_date', '<=', $to_date);
        }

        if($order && $by) {
            $invoices = $invoices->orderBy($order, $by);
        }

        return $invoices->paginate(20);
    }

    public function show($invoice_type, $type, $id)
    {
        // $source = $type == 'clients'?'SALE':'PURCHASE';
        $source = $source = invoiceSource($type);;
        $invoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';

        return Invoice::with(['items', 'partner', 'devise', 'payments'])
        ->where([
            // 'source' => $source,
            // 'type' => $invoiceType,
            'id' => $id
        ])->firstOrFail();
    }

    public function store(Request $request, $invoice_type, $type)
    {
        $request->validate([
            'partner_id'=>'nullable|exists:partners,id',
            'devise_id' => 'required|exists:devises,id',
            'items' => 'required|array',
        ]);

        $invoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';
        $source = invoiceSource($type);

        DB::beginTransaction();

        try {
            $invoice = Invoice::whereDoesntHave('payments')->firstOrNew([
                'source' => $source,
                'type' => $invoiceType,
                'id' => $request->id,
                'status' => false
            ]);

            $invoice->fill($request->only([
                'partner_id',
                'devise_id',
                'billing_date',
                'due_date',
                'subtotal',
                'total',
            ]));

            if(!$invoice->type) {
                $invoice->type = $invoiceType;
            }

            if(!$invoice->source) {
                $invoice->source = $source;
            }

            if(!$invoice->reference) {
                $last = Invoice::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
                ->where(['type' => $invoice->type, 'source' => $invoice->source])->first();

                $ref = ($invoice_type == 'invoices'?'FAC':'AV').'';
                $ref .= substr($source, 0, 3).'/';
                // $ref .= $type == 'clients'?'V':'A';
                $ref .= str_pad($last?$last->number+1:1, 4, '0', STR_PAD_LEFT);
                $invoice->reference = $ref;
            }

            $invoice->save();

            $total_taxe = 0;
            $total = 0;
            $subtotal = 0;
            foreach($request->items as $item) {
                $invoiceItem = InvoiceItem::firstOrNew([
                    'id' => isset($item["id"]) ? $item["id"] : null,
                    'invoice_id' => $invoice->id,
                ]);

                $unit = Unit::findOrFail($item["unit_id"]);

                if($unit->unity > 1) {
                    $qty = $item["qty"]*$unit->unity;
                } else {
                    $qty = $item["qty"];
                }

                $invoiceItem = $invoiceItem->fill([
                    'article_id' => $item["article_id"],
                    'unit_id' => $item["unit_id"],
                    'label' => isset($item["label"])?$item["label"]:'',
                    'price' => $item["price"],
                    'discount' => $item["discount"],
                    'subtotal' => $item["subtotal"],
                    'qty' => $qty
                ]);

                $total_taxe = 0;
                if($invoice->source == 'HOSTING') {
                    $total_tva = 0;
                    $calcul = $item['calcul'];
                    if($calcul == 'D') {
                        $start_date = Carbon::parse($item["start_date"].' 12:00:00');
                        $end_date = Carbon::parse($item["start_date"].' 14:00:00')->addDays($item["qty"]);

                        if($item["subtotal"] > 0) {
                            $total_tva = 1000 * $item["qty"];
                            $total_taxe += $total_tva;
                        }
                    } elseif($calcul == 'H') {
                        $start_date = Carbon::parse($item["start_date"].' '.$item["start_time"]);
                        $end_date = $start_date->copy()->addHours($item["qty"]);
                    }

                    $invoiceItem = $invoiceItem->fill([
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'calcul' => $item["calcul"],
                        'subtotal' => $item["subtotal"] - $total_tva,
                    ]);
                }

                $subtotal += $invoiceItem["subtotal"];
                // $total += $item["subtotal"];

                // $invoiceItem->subtotal = $item["subtotal"];
                $invoiceItem->save();
            }

            // $invoice->subtotal = $total - $total_taxe;
            $invoice->subtotal = $subtotal;
            $invoice->total = $subtotal + $total_taxe;
            $invoice->save();

            InvoiceLog::create([
                'user_id' => auth()->id(),
                'invoice_id' => $invoice->id,
                'action' => $request->id ? 'UPDATE' : 'CREATE',
                'data' => json_encode($invoice),
                'document' => $invoice->reference,
                'request' => json_encode($request->all()),
            ]);

            # All is good
            DB::commit();

            return $invoice;
            // return $this->show($invoice_type, $type, $invoice->id);
        } catch (Throwable $ex) {
            DB::rollBack();

            return response([
                'error' => 'Erreur interne du serveur',
                'message' => $ex,
            ], 500);

            throw $ex;
        }
    }

    public function destroy($invoice_type, $type, $id)
    {
        $source = $source = invoiceSource($type);
        $invoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';

        $invoice = Invoice::where([
            'source' => $source,
            'type' => $invoiceType,
            'id' => $id,
            'status' => false,
        ])->whereDoesntHave('payments')->firstOrFail();

        InvoiceLog::create([
            'user_id' => auth()->id(),
            // 'invoice_id' => $invoice->id,
            'action' => 'DELETE',
            'document' => $invoice->reference,
            'data' => json_encode($invoice),
        ]);

        InvoiceItem::where('invoice_id', $invoice->id)->delete();

        return $invoice->delete();
    }

    public function confirm($invoice_type, $type, $id)
    {
        $source = $source = invoiceSource($type);
        $invoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';

        DB::beginTransaction();

        try {
            $invoice = Invoice::with(['items.article.menus', 'taxes'])->where([
                'source' => $source,
                'type' => $invoiceType,
                'status' => false,
                'id' => $id,
            ])->firstOrFail();

            $errors = [];
            if($invoice->source == 'HOSTING') {
                foreach($invoice->items as $item) {
                    $room = checkValidity($item);
                    if(!$room['free']) {
                        array_push($errors, $room['room']);
                    }
                }
            } elseif($invoice->source == 'BAR') {
                foreach($invoice->items as $item) {
                    $a = checkQuantity($item->article, 'bar', $item->qty, $item->unit_id);
                    if(!$a['available']) {
                        array_push($errors, $a);
                    }
                }
            } elseif($invoice->source == 'RESTAURANT') {
                foreach($invoice->items as $item) {
                    $a = checkMenu($item->article, $item->qty);
                    if(!$a['available']) {
                        array_push($errors, $a);
                    }
                }
            }

            if(count($errors) == 0) {
                if($invoice->source == 'BAR') {
                    $warehouse = Warehouse::where('short_name', 'bar')->first();
                    foreach($invoice->items as $item) {
                        $stock = Stock::where([
                            'warehouse_id' => $warehouse->id,
                            'article_id' => $item->article_id
                        ])->firstOrFail();
                        $stock->qty = $stock->original_qty - $item->original_qty;
                        $stock->save();
                    }

                    # Accounting entries
                    $this->entry($invoice, $warehouse);
                } elseif($invoice->source == 'RESTAURANT') {
                    $warehouse = Warehouse::where('short_name', 'restaurant')->first();
                    foreach($invoice->items as $item) {
                        foreach($item->article->menus as $menu) {
                            $stock = Stock::where([
                                'warehouse_id' => $warehouse->id,
                                'article_id' => $menu->article_id
                            ])->firstOrFail();
                            $stock->qty = $stock->original_qty - $menu->original_qty;
                            $stock->save();
                        }
                    }

                    # Accounting entries
                    $this->entry($invoice, $warehouse);
                } elseif($invoice->source == 'PURCHASE') {
                    // $warehouse = Warehouse::where('short_name', 'purchase')->first();
                    $warehouse = Warehouse::where('short_name', 'MG')->first();
                    foreach($invoice->items as $item) {
                        $stock = Stock::firstOrNew([
                            'warehouse_id' => $warehouse->id,
                            'article_id' => $item->article_id
                        ]);

                        if($stock->id) {
                            $stock->qty = $stock->original_qty + $item->original_qty;
                        } else {
                            $stock->unit_id = $item->unit_id;
                            $stock->qty = $item->original_qty;
                        }

                        $stock->save();
                    }

                    # Accounting entries
                    $this->entry($invoice, $warehouse);
                } elseif($invoice->source == 'HOSTING') {
                    # Accounting entries
                    $this->entry($invoice);
                }

                $invoice->status = true;
                $invoice->save();

                InvoiceLog::create([
                    'user_id' => auth()->id(),
                    'invoice_id' => $invoice->id,
                    'action' => 'CONFIRM',
                    'document' => $invoice->reference,
                    'data' => json_encode($invoice),
                ]);

                # All is good
                DB::commit();
            }

            return [
                'is_valid' => count($errors) == 0,
                'data' => $invoice,
                'errors' => $errors
            ];
        } catch (Throwable $ex) {
            DB::rollBack();

            return response([
                'error' => 'Erreur interne du serveur',
                'message' => $ex,
            ], 500);

            throw $ex;
        }
    }

    public function duplicate(Request $request, $invoice_type, $type, $id)
    {
        // $source = $type == 'clients'?'SALE':'PURCHASE';
        $source = $source = invoiceSource($type);
        $invoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';

        if ($request->refunding) {
            $newInvoiceType = $invoice_type == 'invoices'?'REFUND':'INVOICE';
        } else {
            $newInvoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';
        }

        $oldInvoice = Invoice::where([
            'source' => $source,
            'type' => $invoiceType,
            'id' => $id
        ])->firstOrFail();

        $items = InvoiceItem::where('invoice_id', $oldInvoice->id)->get();

        $newInvoice = new Invoice($oldInvoice->only([
            'partner_id',
            'devise_id',
            'billing_date',
            'due_date',
            'subtotal',
            'total',
        ]));
        $newInvoice->source = $source;
        $newInvoice->type = $newInvoiceType;
        $newInvoice->status = false;

        $last = Invoice::orderBy('id', 'DESC')->first();
        $ref = $newInvoiceType == 'INVOICE'?'F':'A';
        $ref .= $type == 'clients'?'V':'A';
        $ref .= str_pad($last?$last->id+1:1, 4, '0', STR_PAD_LEFT);
        $newInvoice->reference = $ref;

        $newInvoice->save();

        InvoiceLog::create([
            'user_id' => auth()->id(),
            'invoice_id' => $newInvoice->id,
            'action' => 'CREATE',
            'data' => json_encode($newInvoice),
            'document' => $newInvoice->reference,
            'request' => json_encode($request->all()),
        ]);

        foreach ($items as $item) {
            $newItem = new InvoiceItem($item->only([
                'article_id',
                'unit_id',
                'label',
                'price',
                'discount',
                'subtotal',
            ]));

            $unit = Unit::findOrFail($item->unit_id);

            if($unit->unity > 1) {
                $qty = $item->qty*$unit->unity;
            } else {
                $qty = $item->qty;
            }

            $newItem->qty = $qty;
            $newItem->invoice_id = $newInvoice->id;
            $newItem->save();
        }

        return $newInvoice;
    }

    public function removeItem($invoice_type, $type, $id)
    {
        // $source = $type == 'clients'?'SALE':'PURCHASE';
        $source = $source = invoiceSource($type);
        $invoiceType = $invoice_type == 'invoices'?'INVOICE':'REFUND';

        $invoiceItem = InvoiceItem::where([
            'id' => $id
        ])->whereHas('invoice', function($query) use($source, $invoiceType) {
            $query->where([
                'source' => $source,
                'type' => $invoiceType,
            ]);
        })->firstOrFail();

        $invoice = $invoiceItem->invoice;
        $invoice->subtotal -= $invoiceItem->subtotal;
        $invoice->total -= $invoiceItem->subtotal;
        $invoice->save();

        InvoiceLog::create([
            'user_id' => auth()->id(),
            'invoice_id' => $invoice->id,
            'action' => 'UPDATE',
            'data' => json_encode($invoice),
        ]);

        return $invoiceItem->delete();
    }

    private function entry($invoice, $warehouse = null) {
        if ($invoice->type == 'INVOICE') {
            if($invoice->source == 'PURCHASE') {
                $type = 'OUT';
            } else {
                $type = 'IN';
            }
        } elseif ($invoice->type == 'REFUND') {
            if($invoice->source == 'PURCHASE') {
                $type = 'IN';
            } else {
                $type = 'OUT';
            }
        }

        $group = (AccountingEntry::max("document_group") ?? 1) + 1;

        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'chart_account_id' => $invoice->partner->default_account_id,
            'partner_id' => $invoice->partner_id,
            'invoice_id' => $invoice->id,
            'label' => ($invoice->source == 'PURCHASE' ?'Achat ':'Vente ').$invoice->partner->reference,
            $type == 'IN'?'debit':'credit' => $invoice->total,
        ]);

        foreach($invoice->items as $item) {
            AccountingEntry::create([
                'document_group' => $group,
                'fiscal_year_id' => 1,
                'journal_id' => 1,
                'article_id' => $item->article_id,
                'chart_account_id' => $invoice->source == 'PURCHASE' ?
                                    $item->article->expense_account_id :
                                    $item->article->product_account_id,
                'partner_id' => $invoice->partner_id,
                'invoice_id' => $invoice->id,
                'label' => ($invoice->source == 'PURCHASE' ?'Achat ':'Vente ').$item->label,
                $type == 'IN'?'credit':'debit' => $item->subtotal,
            ]);
        }

        if($invoice->taxes) {
            foreach($invoice->taxes as $item) {
                AccountingEntry::create([
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => $item->chart_account_id,
                    'partner_id' => $invoice->partner_id,
                    'invoice_id' => $invoice->id,
                    'label' => $item->name,
                    $type == 'IN'?'credit':'debit' => $item->subtotal,
                    'document_group' => $group,
                ]);
            }
        }

        if($invoice->source == 'HOSTING') {
            $total_taxe = $invoice->total - $invoice->subtotal;
            if($total_taxe > 0) {
                $taxe = ChartAccount::findOrFail(535);
                AccountingEntry::create([
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'chart_account_id' => $taxe->id,
                    'partner_id' => $invoice->partner_id,
                    'invoice_id' => $invoice->id,
                    'label' => 'Taxes de séjour',
                    $type == 'IN'?'credit':'debit' => $total_taxe,
                    'document_group' => $group,
                ]);
            }
        }

        # Warehouse accounting entries
        foreach($invoice->items as $item) {
            if($invoice->source == 'RESTAURANT') {
                $cost_menu = $item->article->menu_cost * $item->original_qty;

                # Stock menu
                $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'article_id' => $item->article_id,
                    'chart_account_id' => $item->article->stock_account_id,
                    'partner_id' => $invoice->partner_id,
                    'invoice_id' => $invoice->id,
                    'warehouse_id' => $warehouse->id,
                    'label' => $warehouse->name.' Entrée '.$item->article->name,
                    $type == 'IN'?'credit':'debit' => $cost_menu,
                ]);
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'article_id' => $item->article_id,
                    'chart_account_id' => $item->article->commodity_account_id,
                    'partner_id' => $invoice->partner_id,
                    'invoice_id' => $invoice->id,
                    'warehouse_id' => $warehouse->id,
                    'label' => $warehouse->name.' Entrée '.$item->article->name,
                    $type == 'IN'?'debit':'credit' => $cost_menu,
                ]);

                # Diestock menu
                $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'article_id' => $item->article_id,
                    'chart_account_id' => $item->article->stock_account_id,
                    'partner_id' => $invoice->partner_id,
                    'invoice_id' => $invoice->id,
                    'warehouse_id' => $warehouse->id,
                    'label' => $warehouse->name.' Sortie '.$item->article->name,
                    $type == 'IN'?'debit':'credit' => $cost_menu,
                ]);
                AccountingEntry::create([
                    'document_group' => $group,
                    'fiscal_year_id' => 1,
                    'journal_id' => 1,
                    'article_id' => $item->article_id,
                    'chart_account_id' => $item->article->commodity_account_id,
                    'partner_id' => $invoice->partner_id,
                    'invoice_id' => $invoice->id,
                    'warehouse_id' => $warehouse->id,
                    'label' => $warehouse->name.' Sortie '.$item->article->name,
                    $type == 'IN'?'credit':'debit' => $cost_menu,
                ]);

                foreach($item->article->menus as $menu) {
                    $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                    $cost = $menu->article->cost_unit * $menu->original_qty;
                    AccountingEntry::create([
                        'document_group' => $group,
                        'fiscal_year_id' => 1,
                        'journal_id' => 1,
                        'article_id' => $menu->article_id,
                        'chart_account_id' => $menu->article->stock_account_id,
                        'partner_id' => $invoice->partner_id,
                        'invoice_id' => $invoice->id,
                        'warehouse_id' => $warehouse->id,
                        'label' => $warehouse->name.' Sortie '.$menu->article->name,
                        $type == 'IN'?'debit':'credit' => $cost,
                    ]);
                    AccountingEntry::create([
                        'document_group' => $group,
                        'fiscal_year_id' => 1,
                        'journal_id' => 1,
                        'article_id' => $menu->article_id,
                        'chart_account_id' => $menu->article->commodity_account_id,
                        'partner_id' => $invoice->partner_id,
                        'invoice_id' => $invoice->id,
                        'warehouse_id' => $warehouse->id,
                        'label' => $warehouse->name.' Sortie '.$menu->article->name,
                        $type == 'IN'?'credit':'debit' => $cost,
                    ]);
                }
            } else {
                if($warehouse) {
                    $group = (AccountingEntry::max("document_group") ?? 1) + 1;
                    $costTotal = $item->original_qty * $item->article->cost_unit;
                    AccountingEntry::create([
                        'document_group' => $group,
                        'fiscal_year_id' => 1,
                        'journal_id' => 1,
                        'article_id' => $item->article_id,
                        'chart_account_id' => $item->article->stock_account_id,
                        'partner_id' => $invoice->partner_id,
                        'invoice_id' => $invoice->id,
                        'warehouse_id' => $warehouse->id,
                        'label' => $warehouse->name.' Sortie '.$item->article->name,
                        $type == 'IN'?'debit':'credit' => $costTotal,
                    ]);
                    AccountingEntry::create([
                        'document_group' => $group,
                        'fiscal_year_id' => 1,
                        'journal_id' => 1,
                        'article_id' => $item->article_id,
                        'chart_account_id' => $item->article->commodity_account_id,
                        'partner_id' => $invoice->partner_id,
                        'invoice_id' => $invoice->id,
                        'warehouse_id' => $warehouse->id,
                        'label' => $warehouse->name.' Sortie '.$item->article->name,
                        $type == 'IN'?'credit':'debit' => $costTotal,
                    ]);
                }
            }
        }
    }
}
