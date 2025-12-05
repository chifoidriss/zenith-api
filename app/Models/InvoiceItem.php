<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['article', 'unit'];
    protected $appends = [
        'dates',
        'original_qty',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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

    public function getDatesAttribute()
    {
        $days = [];
        if($this->start_date) {
            $first = Carbon::parse($this->start_date);
            $last = Carbon::parse($this->end_date);

            for ($i=0; $i <= $last->diffInDays($first); $i++) {
                array_push($days, $first->copy()->addDays($i)->format('Y-m-d'));
            }
        }
        return $days;
    }

    public function getStartTimeAttribute()
    {
        return Carbon::parse($this->start_date)->format('H:i');
    }
}
