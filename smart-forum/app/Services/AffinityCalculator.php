<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\UserInteraction;
use Illuminate\Support\Facades\Cache;

class AffinityCalculator
{
    /**
     * Weight matrix for interaction types (SDD Section 5.4.1)
     */
    protected $weights = [
        'view'     => 1,
        'like'     => 2,
        'download' => 3,
        'comment'  => 5,
    ];

    /**
     * Get the affinity vector for a user.
     * Returns an associative array of category => score (percentage).
     */
    public function getAffinity($userId): array
    {
        $cacheKey = "affinity_user_{$userId}";

        return Cache::remember($cacheKey, 1800, function () use ($userId) {
            // Fetch all interactions with their topics' categories
            $interactions = UserInteraction::where('user_id', $userId)
                ->join('topics', 'user_interactions.topic_id', '=', 'topics.id')
                ->select('topics.ml_category', 'user_interactions.action_type')
                ->get();

            if ($interactions->isEmpty()) {
                return ['General Discussion' => 100];
            }

            // Aggregate scores per category
            $scores = [];
            foreach ($interactions as $interaction) {
                $category = $interaction->ml_category ?? 'General Discussion';
                $weight = $this->weights[$interaction->action_type] ?? 1;
                $scores[$category] = ($scores[$category] ?? 0) + $weight;
            }

            // Normalize to percentages (sum = 100)
            $total = array_sum($scores);
            $normalized = [];
            foreach ($scores as $category => $score) {
                $normalized[$category] = round(($score / $total) * 100, 2);
            }

            // Sort descending
            arsort($normalized);

            return $normalized;
        });
    }

    /**
     * Get recommended topics for a user.
     */
    public function getRecommendations($userId, $limit = 5)
    {
        $affinity = $this->getAffinity($userId);

        // Get top 3 categories by affinity
        $topCategories = array_keys(array_slice($affinity, 0, 3));

        // Get topic IDs the user has already interacted with
        $interactedTopicIds = UserInteraction::where('user_id', $userId)
            ->pluck('topic_id')
            ->toArray();

        // Query topics in those categories, exclude interacted, order by latest
        $recommendations = Topic::whereIn('ml_category', $topCategories)
            ->whereNotIn('id', $interactedTopicIds)
            ->with('group', 'creator')
            ->latest()
            ->limit($limit)
            ->get();

        return $recommendations;
    }

    /**
     * Clear the cached affinity for a user (call when new interaction is logged).
     */
    public function clearCache($userId): void
    {
        Cache::forget("affinity_user_{$userId}");
    }
}