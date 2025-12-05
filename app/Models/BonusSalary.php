<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusSalary extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'bonus_salary';
}
