<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Broadcasting disabled - Pusher library not installed
// All broadcast functionality is disabled in this deployment
if (false) {
    // Broadcast routes disabled for now - using log driver instead
    Broadcast::routes(['middleware' => ['web', 'auth']]);

    // Channel definitions
    Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
        return (int) $user->id === (int) $id;
    });

    Broadcast::channel('orders', function (User $user) {
        return $user->hasAnyRole(['admin', 'kitchen', 'pos']);
    });
}
