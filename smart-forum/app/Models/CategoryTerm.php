<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryTerm extends Model
{
    protected $fillable = ['group_id', 'term', 'category', 'frequency'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}