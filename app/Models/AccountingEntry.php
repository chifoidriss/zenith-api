<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingEntry extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['reference', 'balance'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function salary()
    {
        return $this->belongsTo(Salary::class);
    }

    public function getBalanceAttribute()
    {
        return $this->debit - $this->credit;
    }

    public function getReferenceAttribute()
    {
        if($this->invoice) {
            return $this->invoice->reference;
        } elseif($this->transfer) {
            return $this->transfer->reference;
        } elseif($this->payment) {
            return $this->payment->reference;
        } elseif($this->salary) {
            return $this->salary->reference;
        }
    }
}
