<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['state'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function pay()
    {
        return $this->hasOne(Salary::class);
    }

    public function bonuses()
    {
        return $this->belongsToMany(Bonus::class)->withPivot('value');
    }

    public function indemnities()
    {
        return $this->belongsToMany(Indemnity::class)->withPivot('value');
    }

    public function getStateAttribute() {
        if($this->end_date && $this->end_date < now()) {
            return false;
        }
        return true;
    }
}
