<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\AffinityCalculator;

class UserInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'topic_id',
        'post_id',
        'action_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    protected static function booted()
    {
        static::created(function ($interaction) {
            $calculator = new AffinityCalculator();
            $calculator->clearCache($interaction->user_id);
        });

        static::deleted(function ($interaction) {
            $calculator = new AffinityCalculator();
            $calculator->clearCache($interaction->user_id);
        });
    }
}