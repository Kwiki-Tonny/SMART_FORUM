<?php

namespace App\Services;

use App\Models\CategoryTerm;
use App\Models\Topic;
use Illuminate\Support\Facades\Cache;

class MLTextClassifier
{
    /**
     * Pre-defined academic categories with associated keywords.
     * This is the "training data" for the classifier.
     */
    protected $categories = [
        'Database Systems' => [
            'sql', 'mysql', 'database', 'query', 'join', 'index', 
            'normalization', 'transaction', 'acid', 'foreign key'
        ],
        'Java Programming' => [
            'java', 'class', 'object', 'inheritance', 'polymorphism',
            'jvm', 'jdk', 'spring', 'hibernate', 'maven'
        ],
        'Web Development' => [
            'html', 'css', 'javascript', 'react', 'vue', 'angular',
            'php', 'laravel', 'nodejs', 'api', 'rest', 'ajax'
        ],
        'Network Security' => [
            'firewall', 'encryption', 'ssl', 'tls', 'vpn', 'router',
            'packet', 'cisco', 'ip', 'tcp', 'udp', 'dns'
        ],
        'Algorithms & Data Structures' => [
            'algorithm', 'sort', 'search', 'recursion', 'complexity',
            'binary', 'tree', 'graph', 'hash', 'heap', 'queue', 'stack'
        ],
        'Operating Systems' => [
            'linux', 'windows', 'process', 'thread', 'scheduler',
            'memory', 'file system', 'kernel', 'shell', 'bash'
        ],
        'Software Engineering' => [
            'agile', 'scrum', 'testing', 'unit test', 'debug',
            'refactoring', 'design patterns', 'solid', 'tdd'
        ],
        'General Discussion' => [], // fallback category
    ];

    /**
     * Classify a topic based on its title and body.
     */
    public function classify($title, $body, $groupId): string
    {
        $text = strtolower($title . ' ' . $body);
        
        // Remove special characters
        $text = preg_replace('/[^a-z0-9 ]/', ' ', $text);
        $words = array_count_values(array_filter(explode(' ', $text)));

        $scores = [];

        // Build a scoring map for each category
        foreach ($this->categories as $category => $keywords) {
            $score = 0;

            // If the category has no keywords, skip scoring
            if (empty($keywords)) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (isset($words[$keyword])) {
                    // Term frequency * global importance (if available)
                    $importance = $this->getGlobalImportance($keyword, $groupId);
                    $score += ($words[$keyword] * $importance);
                }
            }
            $scores[$category] = $score;
        }

        // If no scores, assign "General Discussion"
        if (empty($scores) || max($scores) === 0) {
            return 'General Discussion';
        }

        // Get the category with the highest score
        arsort($scores);
        $topCategory = key($scores);

        // Update term frequencies for this group (learning)
        $this->updateTermFrequencies($words, $groupId, $topCategory);

        return $topCategory;
    }

    /**
     * Get the global importance (IDF-like) of a term for a group.
     */
    protected function getGlobalImportance($term, $groupId): float
    {
        $cacheKey = "term_importance_{$groupId}_{$term}";

        return Cache::remember($cacheKey, 3600, function () use ($term, $groupId) {
            $totalTopics = Topic::where('group_id', $groupId)->count();

            if ($totalTopics === 0) {
                return 1.0;
            }

            $termCount = CategoryTerm::where('group_id', $groupId)
                ->where('term', $term)
                ->sum('frequency');

            // Inverse Document Frequency (IDF) – rare terms get higher weight
            $importance = 1 + log($totalTopics / max(1, $termCount));

            return round($importance, 2);
        });
    }

    /**
     * Update term frequencies for a category (learning phase).
     */
    protected function updateTermFrequencies($words, $groupId, $category): void
    {
        foreach ($words as $term => $frequency) {
            if (strlen($term) < 3) {
                continue; // Ignore short words (less than 3 chars)
            }

            CategoryTerm::updateOrCreate(
                [
                    'term' => $term,
                    'group_id' => $groupId,
                ],
                [
                    'category' => $category,
                    'frequency' => \DB::raw('frequency + ' . $frequency),
                ]
            );
        }
    }

    /**
     * Recalculate global importance for all terms in a group.
     * Should be run periodically via a scheduled job.
     */
    public static function recalculateImportance($groupId): void
    {
        $totalTopics = Topic::where('group_id', $groupId)->count();

        if ($totalTopics === 0) {
            return;
        }

        $terms = CategoryTerm::where('group_id', $groupId)->get();

        foreach ($terms as $term) {
            $cacheKey = "term_importance_{$groupId}_{$term->term}";
            $importance = 1 + log($totalTopics / max(1, $term->frequency));
            Cache::put($cacheKey, round($importance, 2), 3600);
        }
    }
}