<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupJoinController extends Controller
{
    public function requestJoin(Request $request, Group $group)
    {
        $user = auth()->user();

        // Check if already a member
        if ($user->groups()->where('group_id', $group->id)->exists()) {
            return redirect()->route('dashboard')->with('info', 'You are already a member of this group.');
        }

        // Create membership with has_agreed_rules = false
        $user->groups()->attach($group->id, ['has_agreed_rules' => false]);

        // Redirect to the rules acceptance page
        return redirect()->route('groups.topics', $group)->with('success', 'Please accept the group rules to join.');
    }
}