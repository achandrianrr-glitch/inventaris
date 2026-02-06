<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Update last_login
        $user->forceFill([
            'last_login' => now(),
        ])->save();

        // Log aktivitas login
        activity_log('auth', 'login', 'Login berhasil: ' . ($user->email ?? '-'));
    }
}
