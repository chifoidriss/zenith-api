<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['reference', 'total_salary', 'total_salary_net', 'total_tax', 'total_retenu', 'total_cnps'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bonuses()
    {
        return $this->belongsToMany(Bonus::class)->withPivot('value');
    }

    public function indemnities()
    {
        return $this->belongsToMany(Indemnity::class)->withPivot('value');
    }

    public function getTotalSalaryNetAttribute()
    {
        return $this->total_salary - $this->total_tax - $this->total_retenu;
    }

    public function getTotalTaxAttribute()
    {
        return $this->tax_irpp_total + $this->tax_cac_total + $this->tax_cfc_total + $this->tax_crtv_total;
    }

    public function getTotalSalaryAttribute()
    {
        return $this->salary_total + $this->bonus_total + $this->indemnity_total + $this->leave_total;
    }

    public function getTotalRetenuAttribute()
    {
        return $this->pvid_total + $this->tax_municipal_total + $this->syndical_total + $this->salary_advance + $this->salary_loan;
    }

    public function getTotalCnpsAttribute()
    {
        return ($this->pvid_total*2) + ($this->total_salary * 0.07) + ($this->total_salary * 0.0175);
    }

    public function getReferenceAttribute()
    {
        return 'R'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }
}
