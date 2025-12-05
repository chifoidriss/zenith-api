<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['due_amount', 'paid_amount', 'unit', 'document_url'];

    public function entry() {
        return $this->hasOne(AccountingEntry::class, 'invoice_id');
    }

    public function partner() {
        return $this->belongsTo(Partner::class)->withDefault([
            'name' => 'Client inconnu',
            'full_name' => 'Client inconnu',
        ]);
    }

    public function devise() {
        return $this->belongsTo(Devise::class);
    }

    public function items() {
        return $this->hasMany(InvoiceItem::class);
    }

    public function logs() {
        return $this->hasMany(InvoiceLog::class);
    }

    public function taxes() {
        return $this->belongsToMany(Taxe::class, 'invoice_taxe')->withPivot('total');
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }

    public function salary() {
        return $this->hasOne(Salary::class);
    }

    public function loan() {
        return $this->hasOne(Loan::class);
    }

    public function advance() {
        return $this->hasOne(Advance::class);
    }

    public function getDueAmountAttribute() {
        $t = Payment::selectRaw('SUM(IF((type="IN"), amount, amount*-1)) as total')
        ->where('invoice_id', $this->id)->first();

        return $this->total - optional($t)->total ?? 0;
    }

    public function getPaidAmountAttribute() {
        $t = Payment::selectRaw('SUM(IF((type="IN"), amount, amount*-1)) as total')
        ->where('invoice_id', $this->id)->first();

        return optional($t)->total ?? 0;
    }

    public function getUnitAttribute() {
        $items = $this->items->groupBy('unit.name');
        $result = [];
        foreach ($items as $key => $value) {
            array_push($result, [
                'unit' => $key,
                'total' => $value->sum('qty')
            ]);
        }
        return collect($result);
    }

    public function getDocumentUrlAttribute() {
        if($this->document) {
            return asset('storage/'. $this->document);
        }
        return null;
    }
}
