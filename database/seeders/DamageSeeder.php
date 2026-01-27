<?php

namespace Database\Seeders;

use App\Models\Damage;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class DamageSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@gmail.com')->value('id');
        $item = Item::query()->inRandomOrder()->first();

        if (!$adminId || !$item) return;

        Damage::query()->create([
            'code' => 'DMG-0001',
            'item_id' => $item->id,
            'borrowing_id' => null,
            'damage_level' => 'minor',
            'description' => 'Kabel agak sobek, perlu perbaikan ringan.',
            'reported_date' => now()->toDateString(),
            'status' => 'pending',
            'solution' => null,
            'completion_date' => null,
            'admin_id' => $adminId,
        ]);
    }
}
