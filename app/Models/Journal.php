<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function defaultAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function productAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function suspenseAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function cashAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function profitAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function lossAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }
}
