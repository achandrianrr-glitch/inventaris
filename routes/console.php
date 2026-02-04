<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // ✅ tambah ini

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// =====================================================
// ✅ TAHAP 16 - SCHEDULER NOTIFIKASI
// - Cek peminjaman jatuh tempo / terlambat
// - Hapus notifikasi lama (>30 hari)
// =====================================================

// cek overdue setiap 30 menit
Schedule::command('borrowings:check-overdue')->everyThirtyMinutes();

// cleanup notif tiap hari jam 02:00
Schedule::command('notifications:cleanup')->dailyAt('02:00');
