<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@gmail.com')->value('id');
        if (!$adminId) return;

        Notification::query()->create([
            'type' => 'stock_low',
            'title' => 'Stok Menipis',
            'message' => 'Beberapa Stock Menipis',
            'reference_id' => null,
            'reference_type' => null,
            'is_read' => false,
            'admin_id' => $adminId,
        ]);
    }
}
