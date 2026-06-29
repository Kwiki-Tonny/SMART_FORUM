<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * List all topics in a group.
     */
    public function index(Group $group)
    {
        $user = auth()->user();

        // Check membership
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        // Check if rules accepted
        if (!$membership->pivot->has_agreed_rules) {
            return response()->json([
                'message' => 'You must accept the group rules first.',
                'requires_rules_acceptance' => true
            ], 403);
        }

        $topics = $group->topics()
            ->with('creator', 'posts.user')
            ->latest()
            ->get();

        return response()->json($topics);
    }

    /**
     * Create a new topic.
     */
    public function store(Request $request, Group $group)
    {
        $user = auth()->user();

        // Check membership and rules
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        if (!$membership->pivot->has_agreed_rules) {
            return response()->json(['message' => 'You must accept the group rules first.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|min:5',
        ]);

        // Create topic (ml_category will be set in Sprint 2)
        $topic = Topic::create([
            'group_id' => $group->id,
            'creator_id' => $user->id,
            'title' => $request->title,
            'body' => $request->body,
            'ml_category' => null, // Will be classified later
        ]);

        // Update user's last_communicated_at (compliance tracking)
        $user->update(['last_communicated_at' => now()]);

        // Load creator relationship
        $topic->load('creator');

        return response()->json($topic, 201);
    }

    /**
     * Get a single topic with its posts.
     */
    public function show(Group $group, Topic $topic)
    {
        $user = auth()->user();

        // Verify topic belongs to group
        if ($topic->group_id !== $group->id) {
            return response()->json(['message' => 'Topic not found in this group.'], 404);
        }

        // Check membership
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        $topic->load('creator', 'posts.user');
        return response()->json($topic);
    }
}