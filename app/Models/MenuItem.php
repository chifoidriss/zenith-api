<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['article', 'unit'];
    protected $appends = ['original_qty'];

    public function menu()
    {
        return $this->belongsTo(Article::class, 'menu_id');
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
