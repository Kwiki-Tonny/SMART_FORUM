<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Topic;
use App\Events\NewPostEvent;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Store a new post (reply) to a topic.
     * Supports nested replies via parent_id and private post access control.
     */
    public function store(Request $request, Group $group, Topic $topic)
    {
        $user = auth()->user();

        // Verify topic belongs to group
        if ($topic->group_id !== $group->id) {
            abort(404, 'Topic not found in this group.');
        }

        // Check membership and rules acceptance
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership || !$membership->pivot->has_agreed_rules) {
            abort(403, 'You must accept the group rules first.');
        }

        // Validate input
        $request->validate([
            'content' => 'required|string|min:1',
            'parent_id' => 'nullable|exists:posts,id',
            'is_private' => 'sometimes|boolean',
            'allowed_users' => 'nullable|array',
            'allowed_users.*' => 'exists:users,id',
        ]);

        // If parent_id is provided, ensure the parent post belongs to the same topic
        if ($request->filled('parent_id')) {
            $parent = $topic->posts()->find($request->parent_id);
            if (!$parent) {
                abort(400, 'Invalid parent post.');
            }
        }

        // Prepare allowed users – must be members of the group
        $allowedUserIds = $request->input('allowed_users', []);
        if (!empty($allowedUserIds)) {
            $groupMemberIds = $group->users()->pluck('users.id')->toArray();
            $allowedUserIds = array_intersect($allowedUserIds, $groupMemberIds);
        }

        // Create the post
        $post = $topic->posts()->create([
            'user_id' => $user->id,
            'content' => $request->content,
            'is_private' => $request->has('is_private'),
            'parent_id' => $request->input('parent_id', null),
        ]);

        // ==============================================
        // PRIVACY: If private, generate exclusions with allowed users
        // ==============================================
        if ($post->is_private) {
            $post->generateExclusions($allowedUserIds);
        }

        // ==============================================
        // RECORD COMMENT INTERACTION (SDD Table 4.3.11)
        // ==============================================
        $post->recordCommentInteraction();

        // Update user's last activity timestamp (compliance tracking)
        $user->update(['last_communicated_at' => now()]);

        // Broadcast real-time event
        //debugger-log
        \Log::info('Broadcasting NewPostEvent for post ID: ' . $post->id);
        broadcast(new NewPostEvent($post))/* ->toOthers() */;

        // ==============================================
        // AJAX RESPONSE (no page reload)
        // ==============================================
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully!',
                'post' => $post->load('user'), // includes user details
            ]);
        }

        // Fallback for non‑AJAX requests (e.g., JavaScript disabled)
        return redirect()
            ->route('topics.show', [$group, $topic])
            ->with('success', 'Reply posted successfully!');
    }

    /**
     * Delete a post (optional).
     * Only the author or admin can delete.
     */
    public function destroy(Group $group, Topic $topic, Post $post)
    {
        $user = auth()->user();

        // Verify post belongs to topic and group
        if ($post->topic_id !== $topic->id || $topic->group_id !== $group->id) {
            abort(404);
        }

        // Authorization: only author or admin
        if ($post->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'You do not have permission to delete this post.');
        }

        // Delete the post (cascade will handle children if any)
        $post->delete();

        return redirect()
            ->route('topics.show', [$group, $topic])
            ->with('success', 'Post deleted.');
    }
}