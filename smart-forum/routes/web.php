<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Web\GroupTopicController;
use App\Http\Controllers\Web\TopicController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\LikeController;
use App\Http\Controllers\Web\GroupJoinController;
use App\Http\Controllers\Web\RecommendationsController;
use App\Http\Controllers\Web\AnalyticsController;
use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Models\Group;
use App\Models\QuizSubmission;
use App\Http\Controllers\Web\QuizController;

// ============================================
// ROOT – WhatsApp Login
// ============================================
Route::get('/', function () {
    return view('auth.login');
});

// ============================================
// AUTH ROUTES – Breeze (login, register, logout, password reset)
// ============================================
require __DIR__.'/auth.php';

// ============================================
// PROTECTED ROUTES – require authentication AND approval
// ============================================
Route::middleware(['auth', 'approved'])->group(function () {

    // ============================================
    // DASHBOARD – Redirect admins, show student dashboard for others
    // ============================================
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Redirect admins to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Student / Lecturer data
        $groups = $user->groups()->withCount('topics')->get();
        $allGroups = Group::withCount('users')->get();

        // Personal stats from user_interactions
        $stats = [
            'likes' => $user->interactions()->where('action_type', 'like')->count(),
            'comments' => $user->interactions()->where('action_type', 'comment')->count(),
            'views' => $user->interactions()->where('action_type', 'view')->count(),
            'downloads' => $user->interactions()->where('action_type', 'download')->count(),
        ];

        // Quiz performance (if QuizSubmission model exists)
        $quizSubmissions = collect();
        $quizzesCompleted = 0;
        $averageScore = 0;

        if (class_exists('App\Models\QuizSubmission')) {
            $quizSubmissions = \App\Models\QuizSubmission::where('user_id', $user->id)
                ->with('quiz')
                ->get();
            $quizzesCompleted = $quizSubmissions->count();
            $averageScore = $quizSubmissions->avg('score') ?? 0;
        }

        return view('student-dashboard', compact(
            'user', 'groups', 'allGroups', 'stats', 'quizSubmissions', 'quizzesCompleted', 'averageScore'
        ));
    })->name('dashboard');

    // ============================================
    // GROUP JOIN REQUEST
    // ============================================
    Route::post('/groups/{group}/request-join', [GroupJoinController::class, 'requestJoin'])
         ->name('groups.request-join');

    // ============================================
    // FORUM ROUTES
    // ============================================
    Route::prefix('groups')->group(function () {
        Route::get('/{group}/topics', [GroupTopicController::class, 'index'])->name('groups.topics');
        Route::patch('/{group}/accept-rules', [GroupTopicController::class, 'acceptRules'])->name('groups.accept-rules');
        Route::get('/{group}/topics/{topic}', [TopicController::class, 'show'])->name('topics.show');
        Route::post('/{group}/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::post('/{group}/topics/{topic}/posts', [PostController::class, 'store'])->name('posts.store');

        // ---- QUIZ LIST ROUTE (MOVED INSIDE AUTH) ----
        Route::get('/{group}/quizzes', [QuizController::class, 'listForGroup'])->name('groups.quizzes');
    });

    // ============================================
    // LIKES (per-post)
    // ============================================
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->name('posts.like');

    // ============================================
    // TOPIC EXPORT (PDF)
    // ============================================
    Route::get('/groups/{group}/topics/{topic}/export', [TopicController::class, 'export'])->name('topics.export');

    // ============================================
    // TOPIC CRUD (Edit, Update, Delete)
    // ============================================
    Route::get('/groups/{group}/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
    Route::put('/groups/{group}/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
    Route::delete('/groups/{group}/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');

    // ============================================
    // PROFILE
    // ============================================
    Route::get('/profile', function () {
        $user = auth()->user();
        $interactions = $user->interactions()->with('topic')->latest()->get();
        return view('profile', compact('user', 'interactions'));
    })->name('profile');

    // ============================================
    // RECOMMENDATIONS
    // ============================================
    Route::get('/recommendations', [RecommendationsController::class, 'index'])->name('recommendations');

    // ============================================
    // ANALYTICS (all authenticated users)
    // ============================================
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // ============================================
    // QUIZZES INDEX (main page)
    // ============================================
    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');

    // ============================================
    // QUIZZES (nested under groups) – CREATE, TAKE, SUBMIT, RESULTS
    // ============================================
    Route::prefix('groups/{group}')->group(function () {
        Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}/take', [QuizController::class, 'take'])->name('quizzes.take');
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('/quizzes/{quiz}/results', [QuizController::class, 'results'])->name('quizzes.results');
    });

    // ============================================
    // FALLBACK POLLING – Check for new posts (global cache)
    // ============================================
    Route::get('/check-new-posts', function () {
        return response()->json([
            'trigger' => Cache::get('new_post_trigger', 0)
        ]);
    })->middleware('auth')->name('check.posts');

    // ============================================
    // ADMIN ROUTES (only for role:admin)
    // ============================================
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {

        // --- Admin Dashboard ---
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // --- Compliance Management ---
        Route::get('/compliance', [ComplianceController::class, 'index'])->name('admin.compliance');
        Route::put('/compliance/settings', [ComplianceController::class, 'updateSettings'])->name('admin.compliance.update');
        Route::get('/compliance/users', [ComplianceController::class, 'getUsers'])->name('admin.compliance.users');

        // --- Group Management ---
        Route::get('/groups/create', [GroupController::class, 'create'])->name('admin.groups.create');
        Route::post('/groups', [GroupController::class, 'store'])->name('admin.groups.store');

        // --- User Management (full CRUD, approval, promotion) ---
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::patch('/users/{user}/approve', [UserController::class, 'approve'])->name('admin.users.approve');
        Route::patch('/users/{user}/reject', [UserController::class, 'reject'])->name('admin.users.reject');
        Route::patch('/users/{user}/promote', [UserController::class, 'promote'])->name('admin.users.promote');
    });
});