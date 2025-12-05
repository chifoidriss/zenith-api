<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartAccount extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['account'];

    public function getAccountAttribute() {
        return rtrim($this->code, '0').' '.$this->name;
    }

    public function journals() {
        return $this->hasMany(Journals::class);
    }

    public function accountType() {
        return $this->belongsTo(AccountType::class);
    }

    public function taxe()
    {
        return $this->belongsToMany(Taxe::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function sale_items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }

}
