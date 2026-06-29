<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostExclusion extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'excluded_user_id',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function excludedUser()
    {
        return $this->belongsTo(User::class, 'excluded_user_id');
    }
}