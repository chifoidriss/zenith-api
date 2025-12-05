<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountType extends Model
{
    use HasFactory;

    protected $with = ['sub', 'main'];

    public function chartaccounts()
    {
        return $this->hasMany(ChartAccount::class);
    }

    /**
     * Get the sub that owns the AccountType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sub(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'sub_id');
    }

    /**
     * Get the main that owns the AccountType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function main(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'main_id');
    }
}
