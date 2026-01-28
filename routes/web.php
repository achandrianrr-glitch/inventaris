<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * HOME
 * - Guest: tampilkan Welcome (breeze)
 * - Auth: lempar ke admin dashboard
 */
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
})->name('home');

/**
 * DASHBOARD (bawaan Breeze)
 * - tetap pakai name 'dashboard' biar Breeze aman
 * - redirect ke admin dashboard
 */
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

/**
 * ADMIN AREA (Tahap 4)
 * - prefix: /admin
 * - name: admin.*
 * - middleware: auth + verified + active
 */
Route::middleware(['auth', 'verified', 'active'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard utama /admin
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        /**
         * NOTIF ACTIONS
         * - aku bedain nama route supaya tidak bentrok dengan admin.notifications (halaman)
         */
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('notifications.markRead');

        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->name('notifications.markAllRead');

        /**
         * PLACEHOLDER PAGES (sementara)
         * - Tahap 5+ akan diganti CRUD beneran
         */
        Route::get('/categories', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Kategori']))
            ->name('categories');

        Route::get('/brands', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Merek']))
            ->name('brands');

        Route::get('/locations', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Lokasi']))
            ->name('locations');

        Route::get('/borrowers', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Peminjam']))
            ->name('borrowers');

        Route::get('/items', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Barang']))
            ->name('items');

        Route::get('/transactions/in', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Barang Masuk']))
            ->name('trx.in');

        Route::get('/transactions/out', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Barang Keluar']))
            ->name('trx.out');

        Route::get('/borrowings', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Peminjaman']))
            ->name('borrowings');

        Route::get('/returns', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Pengembalian']))
            ->name('returns');

        Route::get('/damages', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Kerusakan']))
            ->name('damages');

        Route::get('/opnames', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Stock Opname']))
            ->name('opnames');

        // Halaman list notifikasi (placeholder)
        Route::get('/notifications', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Notifikasi']))
            ->name('notifications');

        Route::get('/reports/inventory', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Laporan Inventaris']))
            ->name('reports.inventory');

        Route::get('/reports/transactions', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Laporan Transaksi']))
            ->name('reports.transactions');

        Route::get('/reports/damages', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Laporan Kerusakan']))
            ->name('reports.damages');

        Route::get('/system/users', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Data Pengguna']))
            ->name('system.users');

        Route::get('/system/settings', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Pengaturan']))
            ->name('system.settings');

        Route::get('/system/activity-logs', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Log Aktivitas']))
            ->name('system.logs');
    });

/**
 * PROFILE (bawaan Breeze)
 * - tetap ada, dikunci auth + active
 */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
