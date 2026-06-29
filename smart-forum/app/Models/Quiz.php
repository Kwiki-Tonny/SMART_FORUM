<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'group_id', 'created_by', 'title', 'description',
        'duration', 'questions', 'allowed_categories',
        'starts_at', 'ends_at', 'is_published'
    ];

    protected $casts = [
        'questions' => 'array',
        'allowed_categories' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(QuizSubmission::class);
    }

    public function isActive(): bool
    {
        if (!$this->is_published) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        return true;
    }

    public function isExpired(): bool
    {
        if (!$this->is_published) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return true;
        return false;
    }

    public function canUserTake(User $user): bool
    {
        if (!$this->isActive()) return false;
        if (!$this->allowed_categories) return true;
        return in_array($user->status, $this->allowed_categories);
    }

    public function getUserSubmission(User $user)
    {
        return $this->submissions()->where('user_id', $user->id)->first();
    }

    public function getTotalQuestionsCount(): int
    {
        return count($this->questions ?? []);
    }

    public function getMaxScore(): float
    {
        $total = 0;
        foreach ($this->questions ?? [] as $question) {
            $total += $question['marks'] ?? 1;
        }
        return $total;
    }
}