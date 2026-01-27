<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\Borrowing;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class BorrowingSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@gmail.com')->value('id');
        $borrower = Borrower::query()->inRandomOrder()->first();
        $item = Item::query()->where('stock_available', '>=', 2)->inRandomOrder()->first();

        if (!$adminId || !$borrower || !$item) return;

        $qty = 2;

        Borrowing::query()->create([
            'code' => 'BRW-0001',
            'borrower_id' => $borrower->id,
            'item_id' => $item->id,
            'qty' => $qty,
            'borrow_type' => 'daily',
            'lesson_hour' => null,
            'subject' => null,
            'teacher' => null,
            'borrow_date' => now()->toDateString(),
            'borrow_time' => now()->format('H:i:s'),
            'return_due' => now()->addDays(2),
            'return_date' => null,
            'return_condition' => null,
            'status' => 'borrowed',
            'admin_id' => $adminId,
            'notes' => 'Dummy pinjam untuk test',
        ]);

        // bikin stok konsisten (simulasi logic tahap 12)
        $item->update([
            'stock_available' => max(0, $item->stock_available - $qty),
            'stock_borrowed' => $item->stock_borrowed + $qty,
        ]);
    }
}
