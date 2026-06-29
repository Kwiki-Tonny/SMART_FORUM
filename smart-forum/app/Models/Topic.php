<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group_id',
        'creator_id',
        'title',
        'body',
        'ml_category',
        'is_private', // <-- ADDED
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_private' => 'boolean', // <-- ADDED
        ];
    }

    // ==============================================
    // ============ RELATIONSHIPS ===================
    // ==============================================

    /**
     * Get the group this topic belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who created this topic.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the posts for this topic.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the interactions for this topic (SDD Table 4.3.11).
     */
    public function interactions()
    {
        return $this->hasMany(UserInteraction::class);
    }

    /**
     * Get the exclusion records for this topic (SDD Table 4.3.10 – topic version).
     */
    public function exclusions()
    {
        return $this->hasMany(TopicExclusion::class);
    }

    // ==============================================
    // ============ PRIVACY METHODS ================
    // ==============================================

    /**
     * Generate exclusion records for all users except:
     * - The author
     * - Admins and lecturers
     * - Explicitly allowed users (passed in)
     */
    public function generateExclusions(array $allowedUserIds = []): void
    {
        if (!$this->is_private) {
            return;
        }

        // Get all users except the author and admins/lecturers
        $excludedUsers = User::where('id', '!=', $this->creator_id)
            ->whereNotIn('role', ['admin', 'lecturer'])
            ->whereNotIn('id', $allowedUserIds)
            ->get();

        // Clear existing exclusions for this topic (avoid duplicates)
        $this->exclusions()->delete();

        foreach ($excludedUsers as $user) {
            TopicExclusion::create([
                'topic_id' => $this->id,
                'excluded_user_id' => $user->id,
            ]);
        }
    }

    /**
     * Check if a specific user is excluded from viewing this topic.
     */
    public function isExcludedFor(User $user): bool
    {
        // Admins and lecturers bypass exclusions
        if ($user->isAdmin() || $user->isLecturer()) {
            return false;
        }

        // Author can see their own topic
        if ($this->creator_id === $user->id) {
            return false;
        }

        return $this->exclusions()
            ->where('excluded_user_id', $user->id)
            ->exists();
    }

    /**
     * Check if this topic is visible to a specific user.
     * (Combines privacy and exclusion logic.)
     */
    public function isVisibleTo(User $user): bool
    {
        if ($user->isAdmin() || $user->isLecturer()) {
            return true;
        }

        if (!$this->is_private) {
            return true;
        }

        if ($this->creator_id === $user->id) {
            return true;
        }

        return !$this->isExcludedFor($user);
    }

    // ==============================================
    // ============ INTERACTION QUERY SCOPES ========
    // ==============================================

    // ... (the rest of your existing methods remain unchanged)
    // I'll include them for completeness, but they are already present.

    /**
     * Scope a query to only include topics with a specific interaction type.
     */
    public function scopeWithInteractionType($query, $type)
    {
        return $query->whereHas('interactions', function ($q) use ($type) {
            $q->where('action_type', $type);
        });
    }

    /**
     * Scope a query to order topics by like count.
     */
    public function scopeOrderByLikes($query, $direction = 'desc')
    {
        return $query->withCount('likes')->orderBy('likes_count', $direction);
    }

    /**
     * Scope a query to order topics by view count.
     */
    public function scopeOrderByViews($query, $direction = 'desc')
    {
        return $query->withCount('views')->orderBy('views_count', $direction);
    }

    // ==============================================
    // ============ INTERACTION METHODS =============
    // ==============================================

    /**
     * Get all likes for this topic.
     */
    public function likes()
    {
        return $this->interactions()->where('action_type', 'like');
    }

    /**
     * Get all views for this topic.
     */
    public function views()
    {
        return $this->interactions()->where('action_type', 'view');
    }

    /**
     * Get all downloads for this topic.
     */
    public function downloads()
    {
        return $this->interactions()->where('action_type', 'download');
    }

    /**
     * Get all comments for this topic.
     */
    public function comments()
    {
        return $this->interactions()->where('action_type', 'comment');
    }

    /**
     * Get the like count for this topic.
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    /**
     * Get the view count for this topic.
     */
    public function getViewsCountAttribute(): int
    {
        return $this->views()->count();
    }

    /**
     * Get the download count for this topic.
     */
    public function getDownloadsCountAttribute(): int
    {
        return $this->downloads()->count();
    }

    /**
     * Get the comment count for this topic.
     */
    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    /**
     * Check if a specific user has liked this topic.
     */
    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a specific user has viewed this topic.
     */
    public function isViewedBy(User $user): bool
    {
        return $this->views()->where('user_id', $user->id)->exists();
    }

    /**
     * Toggle like on this topic.
     * Returns true if liked, false if unliked.
     */
    public function toggleLike(User $user): bool
    {
        $existing = $this->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            return false; // Unliked
        }

        $this->interactions()->create([
            'user_id' => $user->id,
            'action_type' => 'like',
        ]);

        return true; // Liked
    }

    /**
     * Record a view for this topic by a user.
     * Uses updateOrCreate to avoid duplicate views.
     */
    public function recordView(User $user): void
    {
        $this->interactions()->updateOrCreate(
            [
                'user_id' => $user->id,
                'action_type' => 'view',
            ],
            [
                'created_at' => now(),
            ]
        );
    }

    /**
     * Record a download for this topic by a user.
     */
    public function recordDownload(User $user): void
    {
        $this->interactions()->updateOrCreate(
            [
                'user_id' => $user->id,
                'action_type' => 'download',
            ],
            [
                'created_at' => now(),
            ]
        );
    }

    /**
     * Record a comment for this topic by a user.
     */
    public function recordComment(User $user): void
    {
        $this->interactions()->create([
            'user_id' => $user->id,
            'action_type' => 'comment',
        ]);
    }

    /**
     * Get all users who liked this topic.
     */
    public function likedBy()
    {
        return User::whereIn('id', $this->likes()->pluck('user_id'));
    }

    /**
     * Get engagement score for this topic.
     * Weighted formula: likes*2 + comments*3 + views*1 + downloads*2
     */
    public function getEngagementScoreAttribute(): int
    {
        return ($this->likes_count * 2) +
               ($this->comments_count * 3) +
               ($this->views_count * 1) +
               ($this->downloads_count * 2);
    }

    /**
     * Get the post count (top-level posts only, excluding nested replies).
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->posts()->whereNull('parent_id')->count();
    }

    /**
     * Get the total post count (including nested replies).
     */
    public function getTotalPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }

    // ==============================================
    // ============ EAGER LOADING HELPERS ===========
    // ==============================================

    /**
     * Load interaction counts for a list of topics (prevents N+1).
     */
    public static function loadInteractionCounts($topics)
    {
        if ($topics->isEmpty()) {
            return $topics;
        }

        $topicIds = $topics->pluck('id');

        $counts = UserInteraction::whereIn('topic_id', $topicIds)
            ->selectRaw('topic_id, action_type, count(*) as count')
            ->groupBy('topic_id', 'action_type')
            ->get()
            ->groupBy('topic_id');

        foreach ($topics as $topic) {
            $topicCounts = $counts->get($topic->id, collect());
            $topic->likes_count = $topicCounts->where('action_type', 'like')->sum('count') ?? 0;
            $topic->views_count = $topicCounts->where('action_type', 'view')->sum('count') ?? 0;
            $topic->downloads_count = $topicCounts->where('action_type', 'download')->sum('count') ?? 0;
            $topic->comments_count = $topicCounts->where('action_type', 'comment')->sum('count') ?? 0;
        }

        return $topics;
    }
}