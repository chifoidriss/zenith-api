<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];
    // protected $with = ['partner', 'invoice', 'paymentMethod'];

    public function entry() {
        return $this->hasOne(AccountingEntry::class, 'payment_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function devise()
    {
        return $this->belongsTo(Devise::class);
    }

    public function logs() {
        return $this->hasMany(PaymentLog::class);
    }
}
