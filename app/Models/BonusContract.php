<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusContract extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'bonus_contract';
}
