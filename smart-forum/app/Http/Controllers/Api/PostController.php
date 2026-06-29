<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Topic;
use App\Models\Group;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Get all posts for a topic.
     */
    public function index(Group $group, Topic $topic)
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

        // Get posts (Sprint 2 will filter private posts)
        $posts = $topic->posts()->with('user')->latest()->get();

        return response()->json($posts);
    }

    /**
     * Create a new post (reply).
     */
    public function store(Request $request, Group $group, Topic $topic)
    {
        $user = auth()->user();

        // Verify topic belongs to group
        if ($topic->group_id !== $group->id) {
            return response()->json(['message' => 'Topic not found in this group.'], 404);
        }

        // Check membership and rules
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        if (!$membership->pivot->has_agreed_rules) {
            return response()->json(['message' => 'You must accept the group rules first.'], 403);
        }

        $request->validate([
            'content' => 'required|string|min:1',
            'is_private' => 'sometimes|boolean',
        ]);

        // Create post
        $post = Post::create([
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'content' => $request->content,
            'is_private' => $request->is_private ?? false,
        ]);

        // Update user's last_communicated_at
        $user->update(['last_communicated_at' => now()]);

        // Load user relationship
        $post->load('user');

        // =============================================
        // BROADCAST EVENT (Real-time) - Step 5
        // =============================================
        broadcast(new \App\Events\NewPostEvent($post))->toOthers();

        return response()->json($post, 201);
    }
}