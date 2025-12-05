<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['parent'];
    protected $appends = ['full_name'];

    public function getFullNameAttribute() {
        $result = $this->parent ? $this->parent->full_name.' / ' : '';
        $result .= $this->name;
        return $result;
    }

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
