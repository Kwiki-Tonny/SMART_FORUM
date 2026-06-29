<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;

class CheckInactivity extends Command
{
    protected $signature = 'compliance:check-inactivity';
    protected $description = 'Check for inactive users and apply warnings/blacklist';

    public function handle()
    {
        $inactivityDays = Setting::get('inactivity_days', 14);
        $cutoff = now()->subDays($inactivityDays);

        $this->info("Checking users inactive since {$cutoff}...");

        // Get all users who are not already blacklisted
        $users = User::where('status', '!=', 'blacklisted')
                     ->where('last_communicated_at', '<', $cutoff)
                     ->get();

        $this->info("Found {$users->count()} inactive users.");

        foreach ($users as $user) {
            $this->processUser($user);
        }

        $this->info('Compliance check completed.');
    }

    protected function processUser($user)
    {
        $status = $user->status;
        $newStatus = null;
        $reason = "User inactive for more than " . Setting::get('inactivity_days', 14) . " days.";

        switch ($status) {
            case 'active':
                $newStatus = 'warned_once';
                break;
            case 'warned_once':
                $newStatus = 'warned_twice';
                break;
            case 'warned_twice':
                $newStatus = 'blacklisted';
                break;
            default:
                return; // Should never happen
        }

        // Apply the state change
        $user->applyWarning($newStatus, $reason);

        $this->line("User #{$user->id} ({$user->email}): {$status} → {$newStatus}");

        // If blacklisted, send notification (optional)
        if ($newStatus === 'blacklisted') {
            // You can send an email notification here
            // Mail::to($user->email)->send(new BlacklistedNotification($user));
        }
    }
}