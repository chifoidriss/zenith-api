<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['image', 'available', 'type_name', 'cost_unit', 'menu_cost'];
    protected $with = ['category'];

    public function getImageAttribute() {
        if($this->image_path) {
            return asset('storage/'. $this->image_path);
        }
        return null;
    }

    public function getAvailableAttribute() {
        if(count($this->stocks) > 0) {
            $s = $this->stocks->sum('original_qty');
            $u = $this->stocks[0]->unit;
            return ($s/$u->unity) .' '. $u->name;
        }
    }

    public function getCostUnitAttribute() {
        $unit = $this->purchase_unit_id ? $this->purchaseUnit->unity : 1;
        return $this->cost / $unit;
    }

    public function getTypeNameAttribute() {
        if($this->type == 'MENU') {
            return 'Menu';
        } elseif($this->type == 'ROOM') {
            return 'Chambre';
        } elseif($this->type == 'STOCKABLE') {
            return 'Stockable';
        } elseif($this->type == 'CONSUMABLE') {
            return 'Consommable';
        } elseif($this->type == 'SERVICE') {
            return 'Service';
        }
    }

    public function getMenuCostAttribute() {
        $total = 0;
        if($this->type == 'MENU') {
            foreach ($this->menus as $menu) {
                $total += $menu->article->cost_unit * $menu->original_qty;
            }
        }
        return $total;
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function unit() {
        return $this->belongsTo(Unit::class);
    }

    public function saleUnit() {
        return $this->belongsTo(Unit::class, 'sale_unit_id');
    }

    public function purchaseUnit() {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function invoiceItems() {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stocks() {
        return $this->hasMany(Stock::class);
    }

    public function prices() {
        return $this->hasMany(Price::class);
    }

    public function menus() {
        return $this->hasMany(MenuItem::class, 'menu_id');
    }

    public function room() {
        return $this->hasOne(InvoiceItem::class)
        ->whereHas('invoice', function($query) {
            $query->where('status', true);
        })
        ->whereHas('article', function($query) {
            $query->where('type', 'ROOM');
        })
        // ->where('status', true)
        ->where('start_date', '<=', now())
        ->where('end_date', '>=', now());
    }

    public function currentRoom() {
        return $this->hasOne(InvoiceItem::class)
        ->whereHas('invoice', function($query) {
            $query->where('status', true);
        })->whereHas('article', function($query) {
            $query->where('type', 'ROOM');
        })->where('status', true)
        ->where('start_date', '<=', now())
        ->where('end_date', '>=', now());
    }

    public function productAccount() {
        return $this->belongsTo(ChartAccount::class, 'product_account_id');
    }

    public function expenseAccount() {
        return $this->belongsTo(ChartAccount::class, 'expense_account_id');
    }

    public function stockAccount() {
        return $this->belongsTo(ChartAccount::class, 'stock_account_id');
    }

    public function commodityAccount() {
        return $this->belongsTo(ChartAccount::class, 'commodity_account_id');
    }
}
