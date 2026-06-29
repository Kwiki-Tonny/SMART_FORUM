<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Topic;
use App\Models\Post;
use App\Models\UserInteraction;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Determine accessible groups
        if ($user->isAdmin()) {
            $groups = Group::withCount('topics')->get();
        } else {
            $groups = $user->groups()->withCount('topics')->get();
        }

        if ($groups->isEmpty()) {
            return view('analytics', [
                'groups' => $groups,
                'selectedGroup' => null,
                'stats' => null,
                'topicsData' => null,
                'dailyActivity' => null,
                'categoryData' => null,
            ]);
        }

        // Get selected group ID from request, default to first group
        $selectedGroupId = $request->input('group_id', $groups->first()->id);
        $selectedGroup = $groups->firstWhere('id', $selectedGroupId) ?? $groups->first();

        // --- Statistics for selected group ---
        $topicIds = Topic::where('group_id', $selectedGroup->id)->pluck('id');
        $postCount = Post::whereIn('topic_id', $topicIds)->count();
        $likeCount = UserInteraction::whereIn('topic_id', $topicIds)
            ->where('action_type', 'like')
            ->count();
        $viewCount = UserInteraction::whereIn('topic_id', $topicIds)
            ->where('action_type', 'view')
            ->count();
        $downloadCount = UserInteraction::whereIn('topic_id', $topicIds)
            ->where('action_type', 'download')
            ->count();
        $commentCount = UserInteraction::whereIn('topic_id', $topicIds)
            ->where('action_type', 'comment')
            ->count();

        $stats = [
            'total_topics' => $selectedGroup->topics_count,
            'total_posts' => $postCount,
            'total_likes' => $likeCount,
            'total_views' => $viewCount,
            'total_downloads' => $downloadCount,
            'total_comments' => $commentCount,
        ];

        // --- Message volume per topic (bar chart) ---
        $topicsData = Topic::where('group_id', $selectedGroup->id)
            ->withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($topic) {
                return [
                    'title' => $topic->title,
                    'posts' => $topic->posts_count,
                ];
            });

        // --- Daily activity (line chart) last 7 days ---
        $dailyActivity = Post::whereIn('topic_id', $topicIds)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->count];
            });

        // Fill missing days with zero
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dates[$date] = $dailyActivity[$date] ?? 0;
        }
        $dailyActivity = $dates;

        // --- Engagement by category (pie chart) ---
        $categoryData = Topic::where('group_id', $selectedGroup->id)
            ->whereNotNull('ml_category')
            ->selectRaw('ml_category, COUNT(*) as count')
            ->groupBy('ml_category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->ml_category,
                    'count' => $item->count,
                ];
            });

        return view('analytics', [
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
            'stats' => $stats,
            'topicsData' => $topicsData,
            'dailyActivity' => $dailyActivity,
            'categoryData' => $categoryData,
        ]);
    }
}