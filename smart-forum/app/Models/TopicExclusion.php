<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicExclusion extends Model
{
    protected $fillable = ['topic_id', 'excluded_user_id'];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function excludedUser()
    {
        return $this->belongsTo(User::class, 'excluded_user_id');
    }
}