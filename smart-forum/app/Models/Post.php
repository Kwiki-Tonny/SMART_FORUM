<?php

namespace App\Models;

use App\Models\Scopes\PrivacyScope; // <-- ADD THIS
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'topic_id',
        'user_id',
        'content',
        'is_private',
        'parent_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ==============================================
    // ============ GLOBAL SCOPE ====================
    // ==============================================

    protected static function booted()
    {
        static::addGlobalScope(new PrivacyScope);
    }

    // ==============================================
    // ============ RELATIONSHIPS ===================
    // ==============================================

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exclusions()
    {
        return $this->hasMany(PostExclusion::class);
    }

    // ==============================================
    // ============ NESTED REPLIES ==================
    // ==============================================

    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Post::class, 'parent_id')
                    ->with('children', 'user')
                    ->oldest();
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // ==============================================
    // ============ INTERACTIONS ====================
    // ==============================================

    public function recordCommentInteraction(): void
    {
        UserInteraction::create([
            'user_id' => $this->user_id,
            'topic_id' => $this->topic_id,
            'action_type' => 'comment',
        ]);
    }

    public function interaction()
    {
        return UserInteraction::where('topic_id', $this->topic_id)
                              ->where('user_id', $this->user_id)
                              ->where('action_type', 'comment')
                              ->latest()
                              ->first();
    }

    public function isTopicLikedBy(User $user): bool
    {
        return $this->topic->isLikedBy($user);
    }

    // ==============================================
    // ============ LIKES (per‑post) ===============
    // ==============================================

    public function likes()
    {
        return $this->hasMany(UserInteraction::class, 'post_id')
                    ->where('action_type', 'like');
    }

    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function isLikedBy(User $user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function toggleLike(User $user)
    {
        $existing = $this->likes()->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            return false;
        }

        UserInteraction::create([
            'user_id' => $user->id,
            'topic_id' => $this->topic_id,
            'post_id' => $this->id,
            'action_type' => 'like',
        ]);
        return true;
    }

    // ==============================================
    // ============ PRIVACY / EXCLUSIONS ============
    // ==============================================

    /**
     * Generate exclusion records for all users except author, admins, lecturers.
     * Called when a private post is created.
     */
/**
 * Generate exclusions for all users except:
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
        $excludedUsers = User::where('id', '!=', $this->user_id)
            ->whereNotIn('role', ['admin', 'lecturer'])
            ->whereNotIn('id', $allowedUserIds) // <-- Skip allowed users
            ->get();

        // Delete existing exclusions for this post (to avoid duplicates)
        $this->exclusions()->delete();

        foreach ($excludedUsers as $user) {
            PostExclusion::create([
                'post_id' => $this->id,
                'excluded_user_id' => $user->id,
            ]);
        }
    }

    /**
     * Check if a specific user is excluded from viewing this post.
     */
    public function isExcludedFor(User $user): bool
    {
        // Admins and lecturers bypass exclusions
        if ($user->isAdmin() || $user->isLecturer()) {
            return false;
        }

        // Author can see their own post
        if ($this->user_id === $user->id) {
            return false;
        }

        return $this->exclusions()
            ->where('excluded_user_id', $user->id)
            ->exists();
    }

    /**
     * Check if this post is visible to a specific user.
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

        if ($this->user_id === $user->id) {
            return true;
        }

        return !$this->isExcludedFor($user);
    }

    // ==============================================
    // ============ UTILITY METHODS =================
    // ==============================================

    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        return $depth;
    }

    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        return $ancestors->reverse();
    }

    public function descendants()
    {
        $descendants = collect();
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        return $descendants;
    }

    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function root()
    {
        $post = $this;
        while ($post->parent) {
            $post = $post->parent;
        }
        return $post;
    }

    public function getRepliesCountAttribute(): int
    {
        return $this->children()->count();
    }

    public function getTotalRepliesCountAttribute(): int
    {
        return $this->descendants()->count();
    }

    public function getAuthorIdAttribute(): int
    {
        return $this->user_id;
    }

    public function getAuthorNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown User';
    }

    public static function loadWithReplies($post, $depth = 3)
    {
        return $post->load([
            'children' => function ($query) use ($depth) {
                $query->with([
                    'children' => function ($q) use ($depth) {
                        // Recursively load up to depth level
                    }
                ]);
            },
            'user',
        ]);
    }
}