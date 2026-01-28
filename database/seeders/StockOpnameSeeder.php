<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->orderBy('id')->value('id');
        $item = Item::query()->inRandomOrder()->first();

        if (!$adminId) return;

        $system = (int) $item->stock_total;
        $physical = $system; // dummy matched

        StockOpname::query()->create([
            'code' => 'OPN-0001',
            'opname_date' => now()->toDateString(),
            'item_id' => $item->id,
            'system_stock' => $system,
            'physical_stock' => $physical,
            'difference' => $physical - $system,
            'status' => 'normal',
            'validation' => 'matched',
            'admin_id' => $adminId,
            'notes' => 'Opname dummy',
        ]);
    }
}
