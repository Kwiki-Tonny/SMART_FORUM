<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function create(Group $group)
    {
        $user = auth()->user();
        if (!$user->isLecturer() && !$user->isAdmin()) {
            abort(403, 'Only lecturers and admins can create quizzes.');
        }

        return view('quizzes.create', compact('group'));
    }

    public function store(Request $request, Group $group)
    {
        $user = auth()->user();
        if (!$user->isLecturer() && !$user->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:180',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answer' => 'required_if:questions.*.type,multiple_choice,true_false',
            'questions.*.marks' => 'nullable|numeric|min:0',
            'allowed_categories' => 'nullable|array',
            'starts_at' => 'nullable|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $quiz = $group->quizzes()->create([
            'created_by' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'duration' => $request->duration,
            'questions' => $request->questions,
            'allowed_categories' => $request->allowed_categories ?? ['active'],
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'is_published' => $request->has('publish'),
        ]);

        return redirect()->route('groups.topics', $group)
            ->with('success', 'Quiz created successfully!');
    }

        public function take(Group $group, Quiz $quiz)
        {
            $user = auth()->user();

            if ($quiz->group_id !== $group->id) {
                abort(404);
            }

            if (!$quiz->canUserTake($user)) {
                abort(403, 'You are not eligible to take this quiz.');
            }

            if ($quiz->isExpired()) {
                return redirect()->route('quizzes.results', [$group, $quiz])
                    ->with('error', 'This quiz has ended.');
            }

            // Check if already submitted
            $submission = $quiz->getUserSubmission($user);
            if ($submission && $submission->submitted_at) {
                return redirect()->route('quizzes.results', [$group, $quiz]);
            }

            // Create or get submission
            if (!$submission) {
                $submission = $quiz->submissions()->create([
                    'user_id' => $user->id,
                    'started_at' => now(),
                ]);
            }

            return view('quizzes.take', compact('group', 'quiz', 'submission'));
        }

public function submit(Request $request, Group $group, Quiz $quiz)
{
    $user = auth()->user();
    $submission = $quiz->getUserSubmission($user);

    if (!$submission || $submission->submitted_at) {
        return response()->json(['success' => false, 'message' => 'No active submission found.'], 400);
    }

    // Check if time has expired (server-side)
    $startedAt = $submission->started_at;
    $durationSeconds = $quiz->duration * 60;
    if ($startedAt->diffInSeconds(now()) > $durationSeconds + 5) {
        $request->merge(['auto_submit' => 1]);
    }

    $request->validate([
        'answers' => 'required|array',
    ]);

    // ===== SET ANSWERS FIRST =====
    $submission->answers = $request->answers;
    $score = $submission->calculateScore();

    $submission->update([
        'answers' => $request->answers,
        'score' => $score,
        'submitted_at' => now(),
        'is_auto_submitted' => $request->input('auto_submit') == 1,
    ]);

    return response()->json([
        'success' => true,
        'score' => $score,
        'redirect_url' => route('quizzes.results', [$group, $quiz]),
    ]);
}
public function results(Group $group, Quiz $quiz)
{
    $user = auth()->user();
    // ... membership checks ...

    $submission = $quiz->getUserSubmission($user);
    $isCreator = ($quiz->created_by == $user->id);

    // For creator: fetch all submissions
    $allSubmissions = $quiz->submissions()->whereNotNull('submitted_at')->get();
    $scores = $allSubmissions->pluck('score')->filter()->toArray();
    $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    $maxScore = $quiz->getMaxScore();

    // ===== HISTOGRAM =====
    $histogram = [];
    $bins = 5;
    if (count($scores) > 0) {
        $min = 0;
        $max = 100;
        $step = ($max - $min) / $bins;
        // Initialize bins
        for ($i = 0; $i < $bins; $i++) {
            $lower = $min + $i * $step;
            $upper = $min + ($i + 1) * $step;
            $histogram[] = [
                'range' => round($lower) . '-' . round($upper),
                'count' => 0,
            ];
        }
        // Count scores
        foreach ($scores as $score) {
            $index = min(floor(($score - $min) / $step), $bins - 1);
            $histogram[$index]['count']++;
        }
    }

    // Platform average
    $platformAverage = QuizSubmission::whereNotNull('submitted_at')->avg('score') ?? 0;
/* dd($scores, $histogram); */
    return view('quizzes.results', compact(
        'group', 'quiz', 'submission', 'isCreator',
        'scores', 'average', 'maxScore',
        'histogram', 'platformAverage', 'allSubmissions'
    ));
}
}