<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupTopicController extends Controller
{
    public function index(Group $group)
    {
        $user = auth()->user();

        // Check membership
        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            abort(403, 'You are not a member of this group.');
        }

        // Check rules acceptance
        if (!$membership->pivot->has_agreed_rules) {
            return view('groups.rules', compact('group'));
        }

        // ===== TOPICS (filtered by visibility) =====
        $allTopics = $group->topics()
            ->with('creator')
            ->withCount('posts')
            ->latest()
            ->get();

        $topics = $allTopics->filter(function ($topic) use ($user) {
            return $topic->isVisibleTo($user);
        });

        // ===== QUIZZES (available to take) =====
        $quizzes = $group->quizzes()
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->get()
            ->filter(function ($quiz) use ($user) {
                return $quiz->canUserTake($user);
            });

        $quizStatuses = $quizzes->map(function ($quiz) use ($user) {
            $submission = $quiz->getUserSubmission($user);
            return [
                'quiz' => $quiz,
                'submitted' => $submission && $submission->submitted_at !== null,
                'submission' => $submission,
            ];
        });

        // ===== MY QUIZZES (for lecturers/admins) =====
        if ($user->isLecturer() || $user->isAdmin()) {
            $myQuizzes = $group->quizzes()
                ->where('created_by', $user->id)
                ->withCount('submissions')
                ->get()
                ->map(function ($quiz) {
                    $quiz->avg_score = $quiz->submissions()->whereNotNull('submitted_at')->avg('score') ?? 0;
                    $quiz->submissions_count = $quiz->submissions()->whereNotNull('submitted_at')->count();
                    return $quiz;
                });
        } else {
            $myQuizzes = collect();
        }

        // ===== GROUP MEMBERS (for private topic dropdown) =====
        $groupMembers = $group->users()
            ->where('users.id', '!=', $user->id)
            ->get();

        return view('topics.index', compact(
            'group',
            'topics',
            'groupMembers',
            'quizStatuses',
            'myQuizzes'
        ));
    }

    public function acceptRules(Request $request, Group $group)
    {
        $user = auth()->user();
        $action = $request->input('action');

        $membership = $group->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return redirect()->back()->with('error', 'Not a member.');
        }

        if ($action === 'agree') {
            $group->users()->updateExistingPivot($user->id, ['has_agreed_rules' => true]);
            return redirect()->route('groups.topics', $group)->with('success', 'Rules accepted!');
        }

        $group->users()->detach($user->id);
        return redirect()->route('dashboard')->with('error', 'You declined the rules.');
    }
}