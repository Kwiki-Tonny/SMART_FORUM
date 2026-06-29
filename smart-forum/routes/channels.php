<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Public channel for group discussions (any authenticated user can listen)
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    // Allow any authenticated user for development
    // In production, you'd check if the user is a member of the group
    return $user !== null;
});