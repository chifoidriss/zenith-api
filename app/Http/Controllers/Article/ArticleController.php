<?php

namespace App\Http\Controllers\Article;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MenuItem;
use App\Models\Price;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function followup()
    {
        $type = request()->type;
        $source = invoiceSource(request()->source);
        $date = request()->date;

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

        if($type) {
            $articles = Article::where('type', $type);
        } else {
            $articles = Article::query();
        }

        $articles = $articles->withWhereHas('invoiceItems.invoice', function ($query) use ($form_date, $to_date, $source) {
            $query->whereBetween('billing_date', [$form_date, $to_date])
            ->where(['source' => $source, 'type' => 'INVOICE', 'status' => true]);
        })
        ->withSum(['invoiceItems' => function ($query) use ($form_date, $to_date, $source) {
            $query->whereHas('invoice', function($query) use ($form_date, $to_date, $source) {
                $query->whereBetween('billing_date', [$form_date, $to_date])
                ->where(['source' => $source, 'type' => 'INVOICE', 'status' => true]);
            });
        }], 'qty')
        ->withSum(['invoiceItems' => function ($query) use ($form_date, $to_date, $source) {
            $query->whereHas('invoice', function($query) use ($form_date, $to_date, $source) {
                $query->whereBetween('billing_date', [$form_date, $to_date])
                ->where(['source' => $source, 'type' => 'INVOICE', 'status' => true]);
            });
        }], 'subtotal')
        ->withMin(['invoiceItems' => function ($query) use ($form_date, $to_date, $source) {
            $query->whereHas('invoice', function($query) use ($form_date, $to_date, $source) {
                $query->whereBetween('billing_date', [$form_date, $to_date])
                ->where(['source' => $source, 'type' => 'INVOICE', 'status' => true]);
            });
        }], 'subtotal')
        ->orderBy('invoice_items_sum_subtotal', 'DESC')
        ->orderBy('invoice_items_sum_qty', 'DESC');

        return $articles->paginate(10);
    }

    public function dayBook()
    {
        $date = request()->date;
        $firstDay = Carbon::createFromFormat('Y-m', $date)->firstOfMonth();
        $lastDay = Carbon::createFromFormat('Y-m', $date)->lastOfMonth();

        $months = [];
        for ($i=1; $i <= $lastDay->day; $i++) {
            $j = ($i<10?'0':'').$i;
            array_push($months, [
                'name' => $j.$lastDay->format('/m'),
                'date' => $lastDay->format('Y-m-').$j,
            ]);
        }

        $articles = Article::with(['category', 'currentRoom.invoice.partner'])
        ->where('type', 'ROOM')
        ->withWhereHas('invoiceItems', function($query) use ($firstDay, $lastDay) {
            $query->whereHas('invoice', function($query) {
                $query->where('status', true);
            })
            // ->where('status', true)
            ->where(function($query) use ($firstDay, $lastDay) {
                $query->whereBetween('start_date', [$firstDay, $lastDay])
                ->orWhereBetween('end_date', [$firstDay, $lastDay]);
            });
        });

        return [
            'data' => $articles->paginate(10),
            'months' => $months
        ];
    }

    public function index()
    {
        $type = request()->type;
        $warehouse_id = request()->warehouse_id;

        $articles = Article::with(['category', 'prices.unit', 'room.invoice.partner', 'saleUnit', 'purchaseUnit']);
        // if (in_array($type, ['STOCKABLE', 'CONSUMABLE', 'SERVICE', 'ROOM', 'MENU'])) {
        if (in_array($type, ['hosting', 'bar', 'restaurant'])) {
            $articles = $articles->withWhereHas('stocks', function($query) use($type) {
                $query->whereHas('warehouse', function($query) use($type) {
                    $query->where('short_name', $type);
                });
            });
        } elseif($type == 'suppliers') {
            $articles = $articles->where('can_purchase', true);
        } elseif($type == 'all') {
            $articles = $articles;
        } else {
            $types = explode(',', $type);
            $articles = $articles->whereIn('type', $types);
        }

        if($warehouse_id) {
            $articles = $articles->withWhereHas('stocks', function($query) use($warehouse_id) {
                $query->where('warehouse_id', $warehouse_id);
            });
        }

        return $articles->paginate(100);
    }

    public function show($id)
    {
        return Article::with([
            'prices.unit',
            'menus.unit',
            'saleUnit',
            'purchaseUnit',
            'productAccount',
            'expenseAccount',
            'stockAccount',
            'commodityAccount',
        ])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'image' => 'nullable|image',
        ]);

        if($request->id) {
            $article = Article::where([
                'id' => $request->id
            ])->firstOrFail();
        } else {
            $article = new Article();
        }

        $article->fill($request->only([
            'category_id',
            'sale_unit_id',
            'purchase_unit_id',
            'name',
            'barcode',
            'price',
            'cost',
            'type',
            'product_account_id',
            'expense_account_id',
            'stock_account_id',
            'commodity_account_id',
        ]));
        $article->can_sale = $request->can_sale? 1 : 0;
        $article->can_purchase = $request->can_purchase? 1 : 0;
        $article->can_rented = $request->can_rented? 1 : 0;

        if($article->type == 'ROOM') {
            if(!$article->product_account_id) {
                $article->fill(['product_account_id' => 974]);
            }
        } elseif($article->type == 'CONSUMABLE') {
            if(!$article->expense_account_id) {
                $article->fill(['expense_account_id' => 731]);
            }
            if(!$article->stock_account_id) {
                $article->fill(['stock_account_id' => 739]);
            }
            if(!$article->commodity_account_id) {
                $article->fill(['commodity_account_id' => 376]);
            }
        } elseif($article->type == 'STOCKABLE') {
            if(!$article->product_account_id) {
                $article->fill(['product_account_id' => 940]);
            }
            if(!$article->expense_account_id) {
                $article->fill(['expense_account_id' => 725]);
            }
            if(!$article->stock_account_id) {
                $article->fill(['stock_account_id' => 738]);
            }
            if(!$article->commodity_account_id) {
                $article->fill(['commodity_account_id' => 369]);
            }
        } elseif($article->type == 'MENU') {
            if(!$article->product_account_id) {
                $article->fill(['product_account_id' => 947]);
            }
            if(!$article->stock_account_id) {
                $article->fill(['stock_account_id' => 1007]);
            }
            if(!$article->commodity_account_id) {
                $article->fill(['commodity_account_id' => 402]);
            }
        }

        if($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
            $article->image_path = $path;
        }

        if(!$article->reference) {
            $last = Article::orderBy('id', 'DESC')->first();
            $ref = 'P'.str_pad($last?$last->id:1, 4, '0', STR_PAD_LEFT);
            $article->reference = $ref;
        }

        $article->save();

        if($request->prices) {
            foreach(json_decode($request->prices, true) as $item) {
                $price = Price::firstOrNew([
                    'id' => isset($item["id"]) ? $item["id"] : null,
                    'article_id' => $article->id,
                ]);

                $price = $price->fill([
                    'unit_id' => $item["unit_id"],
                    'price' => $item["price"],
                    'min_price' => $item["min_price"],
                    'calcul' => $item["calcul"],
                    'type' => "SALE",
                ]);
                $price->save();
            }
        }

        if($request->menus) {
            foreach(json_decode($request->menus, true) as $item) {
                $menu = MenuItem::firstOrNew([
                    'id' => isset($item["id"]) ? $item["id"] : null,
                    'menu_id' => $article->id,
                ]);

                $unit = Unit::findOrFail($item["unit_id"]);

                if($unit->unity > 1) {
                    $qty = $item["qty"]*$unit->unity;
                } else {
                    $qty = $item["qty"];
                }

                $menu = $menu->fill([
                    'unit_id' => $item["unit_id"],
                    'article_id' => $item["article_id"],
                    'qty' => $qty,
                ]);
                $menu->save();
            }
        }

        return $this->show($article->id);
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        return $article->delete();
    }

    public function removePrice($id)
    {
        $price = Price::where([
            'id' => $id
        ])->firstOrFail();

        return $price->delete();
    }

    public function removeMenuItem($id)
    {
        $menuItem = MenuItem::where([
            'id' => $id
        ])->firstOrFail();

        return $menuItem->delete();
    }

    public function freeUp($id)
    {
        $item = InvoiceItem::where([
            'id' => $id
        ])->firstOrFail();

        $item->status = false;

        return $item->save();
    }

    public function checkValidity(Request $request) {
        $article = Article::findOrFail($request->article_id);

        if ($article->type == 'ROOM') {
            return checkValidity($request);
        } elseif (in_array($article->type, ['STOCKABLE', 'CONSUMABLE'])) {
            return checkQuantity($article, $request->type, $request->qty, $request->unit_id, $request->warehouse_id);
        } elseif ($article->type == 'MENU') {
            return checkMenu($article, $request->qty);
        }
        return 1;
    }

    public function filter()
    {
        $q = request()->q;
        $type = request()->type;

        $articles = Article::query();

        if (in_array($type, ['hosting', 'bar', 'restaurant'])) {
            $articles = $articles->withWhereHas('stocks', function($query) use($type) {
                $query->whereHas('warehouse', function($query) use($type) {
                    $query->where('short_name', $type);
                });
            });
        } elseif($type == 'suppliers') {
            $articles = $articles->where('can_purchase', true);
        } elseif($type == 'all') {
            $articles = $articles;
        } else {
            $types = explode(',', $type);
            $articles = $articles->whereIn('type', $types);
        }

        $articles = $articles->where('name', 'LIKE', "%$q%");

        return $articles->limit(10)->get();
    }
}
