<?php

namespace App\Http\Controllers\Stocking;

use App\Models\Society;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\TransferLog;
use App\Models\Unit;
use App\Models\Warehouse;
use Carbon\Carbon;
use Throwable;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index()
    {
        $transfers = Transfer::with(['partner', 'origin', 'destination']);

        $order = request()->order;
        $form_date = request()->form_date;
        $to_date = request()->to_date;
        $transfer_status = request()->transfer_status;
        $by = request()->by;
        $warehouse = request()->warehouse;

        if($transfer_status == 'active') {
            $transfers = $transfers->where('status', true);
        } elseif($transfer_status == 'pending') {
            $transfers = $transfers->where('status', false);
        }

        if($form_date) {
            $transfers = $transfers->whereDate('billing_date', '>=', $form_date);
        }
        if($to_date) {
            $transfers = $transfers->whereDate('billing_date', '<=', $to_date);
        }
        if($warehouse) {
            $w = Warehouse::where('short_name', $warehouse)->firstOrFail();
            $transfers = $transfers->where(function($query) use ($w) {
                $query->where('origin_warehouse_id', $w->id)
                ->orWhere('destination_warehouse_id', $w->id);
            });
        }

        if($order && $by) {
            $transfers = $transfers->orderBy($order, $by);
        }

        return $transfers->paginate(20);
    }

    public function show($id)
    {
        return Transfer::with(['items', 'partner', 'origin', 'destination'])
        ->where([
            'id' => $id
        ])->firstOrFail();
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id' => 'nullable|exists:partners,id',
            'origin_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id',
            'operation' => 'required|in:TRANSFER,RETURN,APPOINTMENT,DELIVERY',
            'billing_date' => 'required|date',
            'due_date' => 'required|date',
            'items' => 'required|array',
        ]);

        $source = $request->operation;

        DB::beginTransaction();

        try {
            $transfer = Transfer::firstOrNew([
                'id' => $request->id,
                'status' => false
            ]);

            $transfer->fill($request->only([
                'partner_id',
                'origin_warehouse_id',
                'destination_warehouse_id',
                'operation',
                'billing_date',
                'due_date',
            ]));

            if(!$transfer->reference) {
                $last = $last = Transfer::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
                ->where(['operation' => $transfer->operation])->first();

                $ref = substr($source, 0, 3).'/';
                $ref .= str_pad($last?($last->number+1):1, 4, '0', STR_PAD_LEFT);
                $transfer->reference = $ref;
            }

            $transfer->save();

            TransferLog::create([
                'user_id' => auth()->id(),
                'transfer_id' => $transfer->id,
                'action' => $request->id ? 'UPDATE' : 'CREATE',
                'data' => json_encode($transfer),
                'document' => $transfer->reference,
                'request' => json_encode($request->all()),
            ]);

            foreach($request->items as $item) {
                $transferItem = TransferItem::firstOrNew([
                    'id' => isset($item["id"]) ? $item["id"] : null,
                    'transfer_id' => $transfer->id,
                ]);

                $unit = Unit::findOrFail($item["unit_id"]);

                if($unit->unity > 1) {
                    $qty = $item["qty"]*$unit->unity;
                } else {
                    $qty = $item["qty"];
                }

                $transferItem = $transferItem->fill([
                    'article_id' => $item["article_id"],
                    'unit_id' => $item["unit_id"],
                    'qty' => $qty
                ]);
                $transferItem->save();
            }

            # All is good
            DB::commit();

            return $transfer;
            // return $this->show($transfer->id);
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
        $transfer = Transfer::where([
            'id' => $id,
            'status' => false,
        ])->firstOrFail();

        TransferLog::create([
            'user_id' => auth()->id(),
            // 'transfer_id' => $transfer->id,
            'action' => 'DELETE',
            'document' => $transfer->reference,
            'data' => json_encode($transfer),
        ]);

        TransferItem::where('invoice_id', $transfer->id)->delete();

        return $transfer->delete();
    }

    public function confirm($id)
    {
        $transfer = Transfer::where([
            'status' => false,
            'id' => $id,
        ])->firstOrFail();

        DB::beginTransaction();

        try {
            $errors = [];
            foreach($transfer->items as $item) {
                $a = checkQuantity($item->article, null, $item->qty, $item->unit_id, $transfer->origin_warehouse_id);
                if(!$a['available']) {
                    array_push($errors, $a);
                }
            }

            if(count($errors) == 0) {
                foreach($transfer->items as $item) {
                    $origin = Stock::where([
                        'warehouse_id' => $transfer->origin_warehouse_id,
                        'article_id' => $item->article_id
                    ])->firstOrFail();
                    $origin->qty = $origin->original_qty - $item->original_qty;
                    $origin->save();

                    # Accounting entries diestock
                    $this->entry($transfer, $item, $origin->warehouse, true);

                    $destination = Stock::firstOrNew([
                        'warehouse_id' => $transfer->destination_warehouse_id,
                        'article_id' => $item->article_id
                    ]);

                    if($destination->id) {
                        $destination->qty = $destination->original_qty + $item->original_qty;
                    } else {
                        $destination->unit_id = $item->unit_id;
                        $destination->qty = $item->original_qty;
                    }

                    $destination->save();

                    # Accounting entries stock
                    $this->entry($transfer, $item, $destination->warehouse, false);
                }

                $transfer->status = true;
                $transfer->save();

                TransferLog::create([
                    'user_id' => auth()->id(),
                    'transfer_id' => $transfer->id,
                    'action' => 'CONFIRM',
                    'document' => $transfer->reference,
                    'data' => json_encode($transfer),
                ]);
            }

            # All is good
            DB::commit();

            return [
                'is_valid' => count($errors) == 0,
                'data' => $transfer,
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

    public function duplicate(Request $request, $transfer_type, $type, $id)
    {
        // $source = $type == 'clients'?'SALE':'PURCHASE';
        $source = $source = invoiceSource($type);
        $transferType = $transfer_type == 'invoices'?'INVOICE':'REFUND';

        if ($request->refunding) {
            $newInvoiceType = $transfer_type == 'invoices'?'REFUND':'INVOICE';
        } else {
            $newInvoiceType = $transfer_type == 'invoices'?'INVOICE':'REFUND';
        }

        $oldInvoice = Transfer::where([
            'id' => $id
        ])->firstOrFail();

        $items = TransferItem::where('invoice_id', $oldInvoice->id)->get();

        $newInvoice = new Transfer($oldInvoice->only([
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

        $last = Transfer::orderBy('id', 'DESC')->first();
        $ref = $newInvoiceType == 'INVOICE'?'F':'A';
        $ref .= $type == 'clients'?'V':'A';
        $ref .= str_pad($last?$last->id+1:1, 4, '0', STR_PAD_LEFT);
        $newInvoice->reference = $ref;

        $newInvoice->save();

        foreach ($items as $item) {
            $newItem = new TransferItem($item->only([
                'article_id',
                'unit_id',
                'label',
                'price',
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

    public function removeItem($id)
    {
        $transferItem = TransferItem::where([
            'id' => $id
        ])->firstOrFail();

        TransferLog::create([
            'user_id' => auth()->id(),
            'transfer_id' => $transferItem->transfer_id,
            'action' => 'UPDATE',
            'data' => json_encode($transferItem->transfer),
            'document' => $transferItem->transfer->reference,
        ]);

        return $transferItem->delete();
    }

    private function entry($transfer, $item, $warehouse, $is_origin) {
        $cost = $item->article->cost_unit * $item->original_qty;
        $label = $warehouse->name.($is_origin?' Sortie ':' Entrée ').$item->article->name;
        $group = (AccountingEntry::max("document_group") ?? 1) + 1;

        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'transfer_id' => $transfer->id,
            'article_id' => $item->article_id,
            'chart_account_id' => $item->article->stock_account_id,
            'warehouse_id' => $warehouse->id,
            'label' => $label,
            $is_origin?'debit':'credit' => $cost,
        ]);
        AccountingEntry::create([
            'document_group' => $group,
            'fiscal_year_id' => 1,
            'journal_id' => 1,
            'transfer_id' => $transfer->id,
            'article_id' => $item->article_id,
            'chart_account_id' => $item->article->commodity_account_id,
            'warehouse_id' => $warehouse->id,
            'label' => $label,
            $is_origin?'credit':'debit' => $cost,
        ]);
    }
}
