<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = \App\Models\User::query()->orderBy('id')->value('id');
        if (!$adminId) return;


        ActivityLog::query()->create([
            'admin_id' => $adminId,
            'action' => 'CREATE',
            'module' => 'setup',
            'description' => 'Initial dummy log',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder',
            'created_at' => now(),
        ]);
    }
}
