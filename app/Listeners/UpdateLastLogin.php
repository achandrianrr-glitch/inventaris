<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        $user->forceFill(['last_login' => now()])->save();
    }
}
