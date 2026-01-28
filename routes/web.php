<?php

use App\Http\Controllers\Admin\BrandController; // ✅ Tahap 6
use App\Http\Controllers\Admin\BorrowerController; // ✅ Tahap 8
use App\Http\Controllers\Admin\CategoryController; // ✅ Tahap 5
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ItemController; // ✅ Tahap 9 (TAMBAH INI)
use App\Http\Controllers\Admin\LocationController; // ✅ Tahap 7
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
 * ADMIN AREA (Tahap 4+)
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
         * ✅ TAHAP 5 — CATEGORIES (CRUD)
         */
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::patch('/categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
        Route::get('/categories-export', [CategoryController::class, 'export'])->name('categories.export');

        /**
         * ✅ TAHAP 6 — BRANDS (CRUD)
         */
        Route::get('/brands', [BrandController::class, 'index'])->name('brands');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        Route::patch('/brands/{id}/restore', [BrandController::class, 'restore'])->name('brands.restore');

        /**
         * ✅ TAHAP 7 — LOCATIONS (CRUD)
         * - REPLACE placeholder /locations yang lama
         */
        Route::get('/locations', [LocationController::class, 'index'])->name('locations');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
        Route::patch('/locations/{id}/restore', [LocationController::class, 'restore'])->name('locations.restore');

        /**
         * ✅ TAHAP 8 — BORROWERS (CRUD)
         * - REPLACE placeholder /borrowers yang lama
         */
        Route::get('/borrowers', [BorrowerController::class, 'index'])->name('borrowers');
        Route::post('/borrowers', [BorrowerController::class, 'store'])->name('borrowers.store');
        Route::put('/borrowers/{borrower}', [BorrowerController::class, 'update'])->name('borrowers.update');
        Route::delete('/borrowers/{borrower}', [BorrowerController::class, 'destroy'])->name('borrowers.destroy');
        Route::patch('/borrowers/{id}/restore', [BorrowerController::class, 'restore'])->name('borrowers.restore');

        /**
         * ✅ TAHAP 9 — ITEMS (CRUD + Restore + Detail + Export)
         * - REPLACE placeholder /items yang lama
         */
        Route::get('/items', [ItemController::class, 'index'])->name('items');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        Route::patch('/items/{id}/restore', [ItemController::class, 'restore'])->name('items.restore');

        Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');

        Route::get('/items-export-excel', [ItemController::class, 'exportExcel'])->name('items.exportExcel');
        Route::get('/items-export-pdf', [ItemController::class, 'exportPdf'])->name('items.exportPdf');

        /**
         * PLACEHOLDER PAGES (sementara)
         * - Tahap 10+ akan diganti CRUD beneran
         */
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
