<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\Borrowing;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BorrowingSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->orderBy('id')->value('id');
        if (!$adminId) {
            return;
        }

        $borrower = Borrower::query()->inRandomOrder()->first();
        if (!$borrower) {
            return;
        }

        $item = Item::query()->where('stock_available', '>=', 1)->inRandomOrder()->first();
        if (!$item) {
            return;
        }

        // Ambil qty aman (maks 2, tapi tidak boleh lebih dari stok tersedia)
        $qty = min(2, (int) $item->stock_available);
        if ($qty <= 0) {
            return;
        }

        DB::transaction(function () use ($adminId, $borrower, $item, $qty) {
            // Biar tidak bentrok kalau seeder dipanggil berkali-kali
            $code = Borrowing::query()->where('code', 'BRW-0001')->exists()
                ? 'BRW-' . str_pad((string) (Borrowing::count() + 1), 4, '0', STR_PAD_LEFT)
                : 'BRW-0001';

            Borrowing::query()->create([
                'code' => $code,
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

            // Update stok konsisten
            $item->refresh(); // ambil data terbaru
            $item->update([
                'stock_available' => max(0, (int) $item->stock_available - $qty),
                'stock_borrowed' => (int) $item->stock_borrowed + $qty,
            ]);
        });
    }
}
