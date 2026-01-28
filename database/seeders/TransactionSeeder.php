<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = \App\Models\User::query()->orderBy('id')->value('id');
        if (!$adminId) return;


        $item = Item::query()->inRandomOrder()->first();
        if (!$item) return;

        Transaction::query()->create([
            'code' => 'TRX-IN-0001',
            'type' => 'in',
            'item_id' => $item->id,
            'qty' => 5,
            'from_location' => 'Supplier',
            'to_location' => 'Gudang',
            'transaction_date' => now()->toDateString(),
            'admin_id' => $adminId,
            'notes' => 'Stok awal',
            'status' => 'completed',
        ]);
    }
}
