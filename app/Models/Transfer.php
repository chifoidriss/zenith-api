<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function entry() {
        return $this->hasOne(AccountingEntry::class, 'transfer_id');
    }

    public function origin()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destination()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function logs() {
        return $this->hasMany(TransferLog::class);
    }
}
