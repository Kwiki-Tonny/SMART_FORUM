<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB; // For session deletion

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'is_approved',
        'last_communicated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_communicated_at' => 'datetime',
        ];
    }

    // ==============================================
    // ============ RELATIONSHIPS ===================
    // ==============================================

    /**
     * Get the groups this user belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user')
                    ->withPivot('has_agreed_rules')
                    ->withTimestamps();
    }

    /**
     * Get the topics created by this user.
     */
    public function topics()
    {
        return $this->hasMany(Topic::class, 'creator_id');
    }

    /**
     * Get the posts created by this user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the interactions logged for this user.
     */
    public function interactions()
    {
        return $this->hasMany(UserInteraction::class);
    }

    /**
     * Get the quiz submissions for this user.
     */
    public function quizSubmissions()
    {
        return $this->hasMany(QuizSubmission::class);
    }

    /**
     * Get the blacklist logs for this user.
     */
    public function blacklistLogs()
    {
        return $this->hasMany(BlacklistLog::class);
    }

    /**
     * Get the sessions for this user.
     */
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    // ==============================================
    // ============ INTERACTION METHODS =============
    // ==============================================

    /**
     * Get topics liked by this user.
     */
    public function likedTopics()
    {
        return $this->belongsToMany(Topic::class, 'user_interactions')
                    ->where('action_type', 'like')
                    ->withTimestamps();
    }

    /**
     * Check if the user has liked a specific topic.
     */
    public function hasLikedTopic(Topic $topic): bool
    {
        return $this->interactions()
                    ->where('topic_id', $topic->id)
                    ->where('action_type', 'like')
                    ->exists();
    }

    /**
     * Toggle like on a topic.
     */
    public function toggleLikeOnTopic(Topic $topic): bool
    {
        $existing = $this->interactions()
                        ->where('topic_id', $topic->id)
                        ->where('action_type', 'like')
                        ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        $this->interactions()->create([
            'topic_id' => $topic->id,
            'action_type' => 'like',
        ]);

        return true;
    }

    /**
     * Record a view interaction on a topic.
     */
    public function recordViewOnTopic(Topic $topic): void
    {
        $this->interactions()->updateOrCreate(
            [
                'topic_id' => $topic->id,
                'action_type' => 'view',
            ],
            ['created_at' => now()]
        );
    }

    /**
     * Record a download interaction on a topic.
     */
    public function recordDownloadOnTopic(Topic $topic): void
    {
        $this->interactions()->updateOrCreate(
            [
                'topic_id' => $topic->id,
                'action_type' => 'download',
            ],
            ['created_at' => now()]
        );
    }

    /**
     * Record a comment interaction on a topic.
     */
    public function recordCommentOnTopic(Topic $topic): void
    {
        $this->interactions()->create([
            'topic_id' => $topic->id,
            'action_type' => 'comment',
        ]);
    }

    /**
     * Get total likes given.
     */
    public function getTotalLikesGivenAttribute(): int
    {
        return $this->interactions()->where('action_type', 'like')->count();
    }

    /**
     * Get total views.
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->interactions()->where('action_type', 'view')->count();
    }

    /**
     * Get total downloads.
     */
    public function getTotalDownloadsAttribute(): int
    {
        return $this->interactions()->where('action_type', 'download')->count();
    }

    /**
     * Get total comments.
     */
    public function getTotalCommentsAttribute(): int
    {
        return $this->interactions()->where('action_type', 'comment')->count();
    }

    // ==============================================
    // ============ COMPLIANCE METHODS ==============
    // ==============================================

    /**
     * Get the active (non-expired) blacklist log for this user.
     */
    public function getActiveBlacklistLog()
    {
        return $this->blacklistLogs()
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->where('action_type', 'blacklisted')
                    ->latest()
                    ->first();
    }

    /**
     * Check if the user's blacklist has expired.
     */
    public function isBlacklistExpired(): bool
    {
        $log = $this->getActiveBlacklistLog();
        if (!$log) {
            return true; // No active blacklist
        }
        if ($log->expires_at === null) {
            return false; // Permanent blacklist
        }
        return $log->expires_at->isPast();
    }

    /**
     * Apply a warning or blacklist action to the user.
     *
     * @param string $actionType (warned_once, warned_twice, blacklisted)
     * @param string $reason
     */
    public function applyWarning(string $actionType, string $reason): void
    {
        // Determine expiry for blacklist
        $expiresAt = null;
        if ($actionType === 'blacklisted') {
            $duration = Setting::get('blacklist_duration', 30);
            $expiresAt = now()->addDays((int) $duration);
        }

        // Create log entry
        $this->blacklistLogs()->create([
            'reason' => $reason,
            'action_type' => $actionType,
            'expires_at' => $expiresAt,
        ]);

        // Update user status
        $this->update(['status' => $actionType]);

        // If blacklisted, revoke all tokens and sessions
        if ($actionType === 'blacklisted') {
            $this->tokens()->delete();
            DB::table('sessions')->where('user_id', $this->id)->delete();
        }
    }

    /**
     * Check if the user is currently blacklisted (status check only).
     */
    public function isBlacklisted(): bool
    {
        return $this->status === 'blacklisted';
    }

    /**
     * Check if the user is approved.
     */
    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is lecturer.
     */
    public function isLecturer(): bool
    {
        return $this->role === 'lecturer';
    }

    /**
     * Check if user is student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Update last_communicated_at to now.
     */
    public function updateActivity(): void
    {
        $this->update(['last_communicated_at' => now()]);
    }

    // ==============================================
    // ============ QUIZ HELPER =====================
    // ==============================================

    /**
     * Get quiz performance summary for the user.
     * Returns total quizzes taken and average score.
     */
    public function getQuizPerformanceAttribute(): array
    {
        $submissions = $this->quizSubmissions()
                            ->whereNotNull('submitted_at')
                            ->get();

        return [
            'total' => $submissions->count(),
            'average' => $submissions->avg('score') ?? 0,
        ];
    }
}