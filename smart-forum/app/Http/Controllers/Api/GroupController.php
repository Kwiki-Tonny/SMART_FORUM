<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * List all groups the authenticated user belongs to.
     */
    public function index()
    {
        $user = auth()->user();
        $groups = $user->groups()->get();

        return response()->json($groups);
    }

    /**
     * Get a specific group with its topics.
     */
    public function show(Group $group)
    {
        $user = auth()->user();

        // Check if user is a member of this group
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        $group->load('topics.creator', 'topics.posts');
        return response()->json($group);
    }

    /**
     * Join a group (admin creates group, student joins).
     * For simplicity, we'll auto-join if group exists.
     */
    public function join(Group $group)
    {
        $user = auth()->user();

        // Check if already a member
        if ($user->groups()->where('group_id', $group->id)->exists()) {
            return response()->json(['message' => 'Already a member of this group.'], 400);
        }

        // Attach user to group with has_agreed_rules = false (must accept rules)
        $user->groups()->attach($group->id, ['has_agreed_rules' => false]);

        return response()->json([
            'message' => 'Joined group. Please accept the rules to participate.',
            'group' => $group,
            'has_agreed_rules' => false
        ]);
    }

    /**
     * Accept the group rules (onboarding gate).
     */
    public function acceptRules(Group $group)
    {
        $user = auth()->user();

        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        // Update has_agreed_rules to true
        $group->users()->updateExistingPivot($user->id, ['has_agreed_rules' => true]);

        return response()->json([
            'message' => 'Rules accepted. You can now participate in discussions.',
            'has_agreed_rules' => true
        ]);
    }

    /**
     * Create a new group (Admin/Lecturer only).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:groups',
            'description' => 'nullable|string',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Auto-add creator as admin of group (optional)
        $user = auth()->user();
        $group->users()->attach($user->id, ['has_agreed_rules' => true]);

        return response()->json($group, 201);
    }
}