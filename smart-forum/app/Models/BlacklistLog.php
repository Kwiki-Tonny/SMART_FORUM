<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistLog extends Model
{
    protected $fillable = ['user_id', 'reason', 'action_type', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}