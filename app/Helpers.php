<?php

use App\Models\Article;
use App\Models\InvoiceItem;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


if (! function_exists('formatDate')) {
    function formatDate($date) {
        return date("d/m/Y", strtotime($date));
    }
}


if (! function_exists('getPrice')) {
    function getPrice($priceInDecimals, $devise = 'XAF') {
        return number_format($priceInDecimals, 0, ',', ' ') . " $devise";
    }
}

if (! function_exists('number')) {
    function number($number) {
        return number_format($number, 0, ',', ' ');
    }
}

if (! function_exists('setting')) {
    function setting($key, $default = null) {
        $setting = Setting::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }
        if($setting->type == 'image') {
            return asset('storage/'.$setting->value);
        }
        return $setting->value;
    }
}

if (! function_exists('awt')) {
    function awt($word, $locale = null) {
        return $word;

        // return (new AWTClass())->awtTrans($word, $locale);
    }
}

if (! function_exists('checkValidity')) {
    function checkValidity($item) {
        $calcul = $item->calcul;
        if($item->id) {
            $start_date = $item->start_date;
            $end_date = $item->start_date;
        } else {
            if($calcul == 'D') {
                $start_date = Carbon::parse($item->start_date.' 12:00:00');
                $end_date = Carbon::parse($item->start_date.' 14:00:00')->addDays($item->qty)->subMinute();
            } elseif($calcul == 'H') {
                $start_date = Carbon::parse($item->start_date.' '.$item->start_time);
                $end_date = Carbon::parse($item->start_date.' '.$item->start_time)->addHours($item->qty)->subMinute();
            }
        }

        $room = InvoiceItem::where([
            'article_id' => $item->article_id,
            'status' => true
        ])->withWhereHas('invoice', function($query) {
            $query->with('partner')->where('status', true);
        })->where(function($query) use ($start_date, $end_date) {
            $query->whereBetween('start_date', [$start_date, $end_date])
            ->orWhereBetween('end_date', [$start_date, $end_date])
            ->orWhere(function ($query) use ($start_date, $end_date) {
                $query->where('start_date', '<', $start_date)
                ->where('end_date', '>', $end_date);
            });
        })->first();

        return collect([
            'free' => $room ? false:true,
            'room' => $room,
        ]);
    }
}

if (! function_exists('checkQuantity')) {
    function checkQuantity($article, $type, $qty, $unit_id, $warehouse_id = null) {
        if($warehouse_id) {
            $available = Stock::where([
                'article_id' => $article->id,
                'warehouse_id' => $warehouse_id
            ])->sum('qty');
        } else {
            $available = Stock::where('article_id', $article->id)
            ->whereHas('warehouse', function($query) use($type) {
                $query->where('short_name', $type);
            })->sum('qty');
        }


        $unit = Unit::findOrFail($unit_id);

        return collect([
            'available' => $available >= ($qty*$unit->unity),
            'stock' => $available/$unit->unity,
            'article' => $article,
            'label' => round($available/$unit->unity).' '.$unit->name
        ]);
    }
}

if (! function_exists('checkMenu')) {
    function checkMenu($article, $qty) {
        $result = [];
        $warehouse = Warehouse::where('short_name', 'restaurant')->first();

        foreach($article->menus as $menu) {
            $stock_qty = Stock::where([
                'article_id' => $menu->article_id,
                'warehouse_id' => $warehouse->id,
            ])->sum('qty') ?? 0;

            $unit = Unit::findOrFail($menu->unit_id);

            if($stock_qty < ($qty*$menu->original_qty)) {
                array_push($result, [
                    'stock' => $stock_qty/$unit->unity,
                    'article' => $menu->article,
                    'label' => round($stock_qty/$unit->unity).' '.$unit->name
                ]);
            }
        }

        return collect([
            'available' => count($result) == 0,
            'articles' => $result,
        ]);
    }
}

if (! function_exists('partnerType')) {
    function partnerType($type) {
        if ($type == 'clients') {
            return 'CLIENT';
        } elseif ($type == 'suppliers') {
            return 'SUPPLIER';
        } elseif ($type == 'salaries') {
            return 'SALARY';
        } elseif (in_array($type, ['hosting', 'bar', 'restaurant'])) {
            return 'CLIENT';
        }

        return 'OTHER';
    }
}

if (! function_exists('invoiceSource')) {
    function invoiceSource($type) {
        if ($type == 'clients') {
            return 'SALE';
        }
        if ($type == 'suppliers') {
            return 'PURCHASE';
        }
        if ($type == 'salaries') {
            return 'SALARY';
        }
        if ($type == 'hosting') {
            return 'HOSTING';
        }
        if ($type == 'bar') {
            return 'BAR';
        }
        if ($type == 'restaurant') {
            return 'RESTAURANT';
        }

        return 'OTHER';
    }
}
