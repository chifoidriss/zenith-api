<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['full_name', 'name'];

    public function defaultAccount() {
        return $this->belongsTo(ChartAccount::class, 'default_account_id');
    }

    public function invoices() {
        return $this->hasMany(Invoice::class);
    }

    public function dueInvoices() {
        return $this->hasMany(Invoice::class)
        ->where('status', true)
        ->whereDate('due_date', '<=', date('Y-m-d'))
        ->where(function($query) {
            $query->whereHas('payments', function ($query) {
                $query->select('invoice_id')
                    ->havingRaw('invoices.total>sum(amount)')
                    ->groupBy('invoice_id');
            })->orDoesntHave('payments');
        });
    }

    public function unpaidInvoices() {
        return $this->hasMany(Invoice::class)
        ->where(function($query) {
            $query->whereHas('payments', function ($query) {
                $query->select('invoice_id')
                    ->havingRaw('invoices.total>sum(amount)')
                    ->groupBy('invoice_id');
            })->orDoesntHave('payments');
        });
    }

    public function paidInvoices() {
        return $this->hasMany(Invoice::class)
        ->whereHas('payments', function ($query) {
            $query->select('invoice_id')
                ->havingRaw('invoices.total<=sum(amount)')
                ->groupBy('invoice_id');
        });
    }

    public function getFullNameAttribute() {
        $result = $this->title ? $this->title.'. ' : '';
        $result .= $this->last_name .' '.$this->first_name;
        return $result;
    }

    public function getNameAttribute() {
        return $this->first_name.' '.$this->last_name;
    }
}
