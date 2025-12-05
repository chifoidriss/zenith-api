<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taxe extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function chartAccount() {
        return $this->belongsTo(ChartAccount::class, 'chart_account_id');
    }
}
