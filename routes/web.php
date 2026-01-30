<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BorrowerController;
use App\Http\Controllers\Admin\BorrowingController; // ✅ Tahap 12
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\TransactionInController;
use App\Http\Controllers\Admin\TransactionOutController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * HOME
 * - Guest: Welcome (breeze)
 * - Auth: redirect ke admin dashboard
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
 * DASHBOARD bawaan Breeze
 * - redirect ke admin dashboard
 */
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

/**
 * ADMIN AREA
 */
Route::middleware(['auth', 'verified', 'active'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Notification actions (PATCH)
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('notifications.markRead');

        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->name('notifications.markAllRead');

        /**
         * ✅ Tahap 5 — Categories
         */
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::patch('/categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
        Route::get('/categories-export', [CategoryController::class, 'export'])->name('categories.export');

        /**
         * ✅ Tahap 6 — Brands
         */
        Route::get('/brands', [BrandController::class, 'index'])->name('brands');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        Route::patch('/brands/{id}/restore', [BrandController::class, 'restore'])->name('brands.restore');

        /**
         * ✅ Tahap 7 — Locations
         */
        Route::get('/locations', [LocationController::class, 'index'])->name('locations');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
        Route::patch('/locations/{id}/restore', [LocationController::class, 'restore'])->name('locations.restore');

        /**
         * ✅ Tahap 8 — Borrowers
         */
        Route::get('/borrowers', [BorrowerController::class, 'index'])->name('borrowers');
        Route::post('/borrowers', [BorrowerController::class, 'store'])->name('borrowers.store');
        Route::put('/borrowers/{borrower}', [BorrowerController::class, 'update'])->name('borrowers.update');
        Route::delete('/borrowers/{borrower}', [BorrowerController::class, 'destroy'])->name('borrowers.destroy');
        Route::patch('/borrowers/{id}/restore', [BorrowerController::class, 'restore'])->name('borrowers.restore');

        /**
         * ✅ Tahap 9 — Items
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
         * ✅ Tahap 10 — Transaction IN
         */
        Route::get('/transactions/in', [TransactionInController::class, 'index'])->name('transactions.in');
        Route::post('/transactions/in', [TransactionInController::class, 'store'])->name('transactions.in.store');

        /**
         * ✅ Tahap 11 — Transaction OUT
         */
        Route::get('/transactions/out', [TransactionOutController::class, 'index'])->name('transactions.out');
        Route::post('/transactions/out', [TransactionOutController::class, 'store'])->name('transactions.out.store');

        /**
         * ✅ Alias route lama (opsional)
         */
        Route::redirect('/trx/out', '/admin/transactions/out')->name('trx.out');
        Route::redirect('/trx/in', '/admin/transactions/in')->name('trx.in');

        /**
         * ✅ Tahap 12 — Borrowings (REAL ✅✅✅)
         * - menggantikan placeholder /borrowings
         */
        Route::get('/borrowings', [BorrowingController::class, 'index'])->name('admin.borrowings');
        Route::post('/borrowings', [BorrowingController::class, 'store'])->name('admin.borrowings.store');

        /**
         * PLACEHOLDER pages (Tahap 13+ / lainnya)
         */
        Route::get('/returns', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Pengembalian']))
            ->name('returns');

        Route::get('/damages', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Kerusakan']))
            ->name('damages');

        Route::get('/opnames', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Stock Opname']))
            ->name('opnames');

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
 */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
