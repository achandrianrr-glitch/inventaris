<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function handle(Logout $event): void
    {
        $user = $event->user;
        if ($user) {
            activity_log('auth', 'logout', 'Logout: ' . ($user->email ?? '-'));
        }
    }
}
