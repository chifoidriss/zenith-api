<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['article', 'unit'];
    protected $appends = ['original_qty'];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
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
}
