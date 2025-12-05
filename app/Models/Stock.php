<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['available', 'original_qty'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getQtyAttribute()
    {
        return $this->attributes['qty']/$this->unit->unity;
    }

    public function getOriginalQtyAttribute()
    {
        return $this->attributes['qty'];
    }

    public function getAvailableAttribute()
    {
        return $this->attributes['qty'] / $this->unit->unity;
    }
}
