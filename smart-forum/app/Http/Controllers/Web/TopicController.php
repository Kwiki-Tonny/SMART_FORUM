<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Topic;
use App\Services\MLTextClassifier;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TopicController extends Controller
{
    /**
     * Display a specific topic with all its posts and nested replies.
     */
    public function show(Group $group, Topic $topic)
    {
        $user = auth()->user();

        // Verify topic belongs to group
        if ($topic->group_id !== $group->id) {
            abort(404);
        }

        // Check membership and rules acceptance
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership || !$membership->pivot->has_agreed_rules) {
            abort(403, 'You must accept the group rules first.');
        }

        // ==============================================
        // PRIVACY: Check if the user can view this topic
        // ==============================================
        if (!$topic->isVisibleTo($user)) {
            abort(403, 'You do not have permission to view this topic.');
        }

        // Record view interaction
        $topic->recordView($user);

        // Load relationships
        $topic->load([
            'creator',
            'posts' => function ($query) {
                $query->whereNull('parent_id')
                    ->with([
                        'user',
                        'children' => function ($q) {
                            $q->with(['user', 'children.user']);
                        }
                    ])
                    ->oldest();
            }
        ]);

        // Get group members (excluding the current user)
        $groupMembers = $group->users()->where('users.id', '!=', $user->id)->get();

        return view('topics.show', compact('group', 'topic', 'groupMembers'));
    }

    /**
     * Store a new topic in a group – with ML classification and privacy.
     */
    public function store(Request $request, Group $group)
    {
        $user = auth()->user();

        // Check membership and rules acceptance
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership || !$membership->pivot->has_agreed_rules) {
            abort(403, 'You must accept the group rules first.');
        }

        // Validate input
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|min:5',
            'is_private' => 'sometimes|boolean',
            'allowed_users' => 'nullable|array',
            'allowed_users.*' => 'exists:users,id',
        ]);

        // Prepare allowed users – must be members of the group
        $allowedUserIds = $request->input('allowed_users', []);
        if (!empty($allowedUserIds)) {
            $groupMemberIds = $group->users()->pluck('users.id')->toArray();
            $allowedUserIds = array_intersect($allowedUserIds, $groupMemberIds);
        }

        // ==============================================
        // ML CLASSIFICATION (Sprint 2)
        // ==============================================
        $classifier = new MLTextClassifier();
        $mlCategory = $classifier->classify(
            $request->title,
            $request->body,
            $group->id
        );

        // Create the topic with the classified category and privacy flag
        $topic = $group->topics()->create([
            'title' => $request->title,
            'body' => $request->body,
            'creator_id' => $user->id,
            'ml_category' => $mlCategory,
            'is_private' => $request->has('is_private'),
        ]);

        // ==============================================
        // PRIVACY: If private, generate exclusions
        // ==============================================
        if ($topic->is_private) {
            $topic->generateExclusions($allowedUserIds);
        }

        // Update user's last activity timestamp (compliance tracking)
        $user->update(['last_communicated_at' => now()]);

        // ==============================================
        // RECORD TOPIC CREATION AS A COMMENT INTERACTION
        // ==============================================
        $user->recordCommentOnTopic($topic);

        return redirect()
            ->route('groups.topics', $group)
            ->with('success', 'Topic created successfully!');
    }

    /**
     * Show the topic edit form.
     */
    public function edit(Group $group, Topic $topic)
    {
        $user = auth()->user();

        if ($topic->creator_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'You do not have permission to edit this topic.');
        }

        return view('topics.edit', compact('group', 'topic'));
    }

    /**
     * Update an existing topic.
     */
    public function update(Request $request, Group $group, Topic $topic)
    {
        $user = auth()->user();

        if ($topic->creator_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'You do not have permission to update this topic.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|min:5',
        ]);

        $topic->update([
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()
            ->route('topics.show', [$group, $topic])
            ->with('success', 'Topic updated successfully!');
    }

    /**
     * Delete a topic.
     */
    public function destroy(Group $group, Topic $topic)
    {
        $user = auth()->user();

        if ($topic->creator_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'You do not have permission to delete this topic.');
        }

        $topic->delete();

        return redirect()
            ->route('groups.topics', $group)
            ->with('success', 'Topic deleted successfully!');
    }

    /**
     * Export topic to PDF (records download interaction).
     */

    public function export(Group $group, Topic $topic)
    {
        $user = auth()->user();

        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership || !$membership->pivot->has_agreed_rules) {
            abort(403, 'You must accept the group rules first.');
        }

        // Record download interaction
        $topic->recordDownload($user);

        // Load relationships for PDF
        $topic->load('group', 'creator');

        $pdf = Pdf::loadView('pdfs.topic', compact('topic'));
        return $pdf->download($topic->slug ?? $topic->id . '.pdf');
    }
}