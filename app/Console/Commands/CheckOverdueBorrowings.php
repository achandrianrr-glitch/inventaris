<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOverdueBorrowings extends Command
{
    protected $signature = 'borrowings:check-overdue';
    protected $description = 'Check due/overdue borrowings and create notifications';

    public function handle(): int
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {

            // ambil pinjaman aktif (borrowed/late) yang belum return
            $rows = Borrowing::query()
                ->whereNull('return_date')
                ->whereIn('status', ['borrowed', 'late'])
                ->whereNotNull('return_due')
                ->with(['borrower:id,name', 'item:id,code,name'])
                ->get(['id', 'code', 'borrower_id', 'item_id', 'return_due', 'status']);

            $admins = User::query()->where('status', 'active')->get(['id']);

            foreach ($rows as $b) {
                $due = Carbon::parse($b->return_due);

                // 1) Due soon: H-1 (opsional, biar tidak terlalu spam)
                if ($b->status === 'borrowed' && $due->isSameDay($now->copy()->addDay())) {
                    foreach ($admins as $a) {
                        app(NotificationService::class)->createForAdmin(
                            (int)$a->id,
                            'overdue',
                            'Peminjaman mendekati jatuh tempo',
                            "Kode {$b->code} • {$b->borrower?->name} • {$b->item?->code} {$b->item?->name} jatuh tempo besok.",
                            'borrowing',
                            (int)$b->id
                        );
                    }
                }

                // 2) Overdue: jika lewat due
                if ($due->lt($now)) {
                    // update status menjadi late kalau masih borrowed
                    if ($b->status === 'borrowed') {
                        $b->status = 'late';
                        $b->save();
                    }

                    foreach ($admins as $a) {
                        app(NotificationService::class)->createForAdmin(
                            (int)$a->id,
                            'overdue',
                            'Peminjaman terlambat',
                            "Kode {$b->code} • {$b->borrower?->name} • {$b->item?->code} {$b->item?->name} sudah lewat jatuh tempo.",
                            'borrowing',
                            (int)$b->id
                        );
                    }
                }
            }
        });

        $this->info('Overdue check done.');
        return self::SUCCESS;
    }
}
