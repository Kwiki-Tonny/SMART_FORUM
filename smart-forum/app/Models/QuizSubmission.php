<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSubmission extends Model
{
    protected $fillable = [
        'quiz_id', 'user_id', 'answers', 'score',
        'is_auto_submitted', 'breach_count', 'started_at', 'submitted_at'
    ];

    protected $casts = [
        'answers' => 'array',
        'is_auto_submitted' => 'boolean',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
public function calculateScore(): float
{
    $questions = $this->quiz->questions ?? [];
    $answers = $this->answers ?? [];

    if (empty($questions)) {
        return 0;
    }

    $score = 0;
    $total = 0;

    foreach ($questions as $index => $question) {
        $marks = $question['marks'] ?? 1;
        $total += $marks;

        $userAnswer = $answers[$index] ?? null;
        $correctAnswer = $question['correct_answer'] ?? null;

        // Skip if no correct answer (e.g., short answer without defined answer)
        if ($correctAnswer === null) {
            continue;
        }

        if ($userAnswer !== null) {
            // For short answer, do case‑insensitive trim
            if (($question['type'] ?? '') === 'short_answer') {
                if (strcasecmp(trim($userAnswer), trim($correctAnswer)) === 0) {
                    $score += $marks;
                }
            } elseif ($userAnswer === $correctAnswer) {
                $score += $marks;
            }
        }
    }

    return $total > 0 ? round(($score / $total) * 100, 2) : 0;
}
}