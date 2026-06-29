<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Group;
use App\Models\Topic;
use App\Models\Post;
use App\Models\UserInteraction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats
        $totalUsers = User::count();
        $totalGroups = Group::count();
        $totalTopics = Topic::count();
        $totalPosts = Post::count();
        $pendingApprovals = User::where('is_approved', false)->count();
        $blacklistedUsers = User::where('status', 'blacklisted')->count();

        // Pending users
        $pendingUsers = User::where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->get();

        // Recent activity (last 10 interactions)
        $recentActivity = UserInteraction::with('user', 'topic')
            ->latest()
            ->limit(10)
            ->get();

        // Recently joined users
        $recentUsers = User::latest()
            ->limit(5)
            ->get();

        // Groups with most topics
        $topGroups = Group::withCount('topics')
            ->orderBy('topics_count', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalGroups',
            'totalTopics',
            'totalPosts',
            'pendingApprovals',
            'blacklistedUsers',
            'pendingUsers',
            'recentActivity',
            'recentUsers',
            'topGroups'
        ));
    }

    public function approveUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['is_approved' => true]);

        return redirect()->back()->with('success', "User '{$user->name}' approved successfully!");
    }

    public function rejectUser($userId)
    {
        $user = User::findOrFail($userId);
        $name = $user->name;
        $user->delete();

        return redirect()->back()->with('success', "User '{$name}' rejected and removed.");
    }
}