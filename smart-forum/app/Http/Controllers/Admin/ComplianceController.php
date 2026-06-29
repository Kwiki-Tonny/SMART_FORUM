<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlacklistLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function index()
    {
        $settings = [
            'inactivity_days' => Setting::get('inactivity_days', 14),
            'blacklist_duration' => Setting::get('blacklist_duration', 30),
        ];

        $blacklistedUsers = User::where('status', 'blacklisted')
                                ->with('blacklistLogs')
                                ->get();

        $warnedUsers = User::whereIn('status', ['warned_once', 'warned_twice'])
                           ->with('blacklistLogs')
                           ->get();

        $logs = BlacklistLog::with('user')
                            ->latest()
                            ->limit(50)
                            ->get();

        return view('admin.compliance', compact('settings', 'blacklistedUsers', 'warnedUsers', 'logs'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'inactivity_days' => 'required|integer|min:1|max:365',
            'blacklist_duration' => 'required|integer|min:1|max:365',
        ]);

        Setting::set('inactivity_days', $request->inactivity_days);
        Setting::set('blacklist_duration', $request->blacklist_duration);

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    public function getUsers()
    {
        $users = User::with('blacklistLogs')
                     ->whereIn('status', ['warned_once', 'warned_twice', 'blacklisted'])
                     ->get();

        return response()->json($users);
    }
}