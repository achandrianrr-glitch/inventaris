<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BorrowerController;
use App\Http\Controllers\Admin\BorrowingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DamageController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\Admin\TransactionInController;
use App\Http\Controllers\Admin\TransactionOutController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureUserIsActive;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * HOME
 * - Guest: Welcome (Breeze)
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
})->middleware(['auth', 'verified', EnsureUserIsActive::class])->name('dashboard');

/**
 * ADMIN AREA
 */
Route::middleware(['auth', 'verified', EnsureUserIsActive::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        /**
         * ✅ Tahap 16 — Notifications
         */
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread_count');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');

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

        // Alias route lama (opsional)
        Route::redirect('/trx/out', '/admin/transactions/out');
        Route::redirect('/trx/in', '/admin/transactions/in');

        /**
         * ✅ Tahap 12 — Borrowings
         */
        Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings');
        Route::post('/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');

        /**
         * ✅ Tahap 13 — Returns (Pengembalian)
         */
        Route::get('/returns', [ReturnController::class, 'index'])->name('returns');
        Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');
        Route::get('/returns/active-borrowings', [ReturnController::class, 'activeBorrowings'])->name('returns.activeBorrowings');

        /**
         * ✅ Tahap 14 — Damages (Kerusakan)
         */
        Route::get('/damages', [DamageController::class, 'index'])->name('damages');
        Route::post('/damages', [DamageController::class, 'store'])->name('damages.store');
        Route::patch('/damages/{damage}', [DamageController::class, 'update'])->name('damages.update');

        /**
         * ✅ Tahap 15 — Stock Opname
         */
        Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->name('stock_opnames');
        Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->name('stock_opnames.store');
        Route::get('/stock-opnames/items', [StockOpnameController::class, 'itemsByLocation'])->name('stock_opnames.items');

        Route::get('/stock-opnames/review', [StockOpnameController::class, 'review'])->name('stock_opnames.review');
        Route::patch('/stock-opnames/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->name('stock_opnames.approve');

        Route::get('/stock-opnames/export/csv', [StockOpnameController::class, 'exportCsv'])->name('stock_opnames.export_csv');

        // OPTIONAL alias supaya URL lama /admin/opnames tidak 404
        Route::redirect('/opnames', '/admin/stock-opnames');
        Route::redirect('/opnames/review', '/admin/stock-opnames/review');
        Route::redirect('/opnames/items', '/admin/stock-opnames/items');
        Route::redirect('/opnames/export/csv', '/admin/stock-opnames/export/csv');

        /**
         * ✅ Tahap 17 — Reports (Excel/PDF)
         */
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');

        // ✅ FIX: Halaman submenu laporan (biar link sidebar tidak 404)
        Route::get('/reports/inventory', fn() => redirect('/admin/reports?tab=inventory'))->name('reports.inventory');
        Route::get('/reports/transactions', fn() => redirect('/admin/reports?tab=transactions'))->name('reports.transactions');
        Route::get('/reports/damages', fn() => redirect('/admin/reports?tab=damages'))->name('reports.damages');

        // Excel (single report)
        Route::get('/reports/inventory/excel', [ReportController::class, 'inventoryExcel'])->name('reports.inventory_excel');
        Route::get('/reports/transactions/excel', [ReportController::class, 'transactionsExcel'])->name('reports.transactions_excel');
        Route::get('/reports/damages/excel', [ReportController::class, 'damagesExcel'])->name('reports.damages_excel');
        Route::get('/reports/borrowings/excel', [ReportController::class, 'borrowingsExcel'])->name('reports.borrowings_excel');

        // Excel (multi-sheet workbook)
        Route::get('/reports/workbook/excel', [ReportController::class, 'workbookExcel'])->name('reports.workbook_excel');

        // PDF
        Route::get('/reports/inventory/pdf', [ReportController::class, 'inventoryPdf'])->name('reports.inventory_pdf');
        Route::get('/reports/transactions/pdf', [ReportController::class, 'transactionsPdf'])->name('reports.transactions_pdf');
        Route::get('/reports/damages/pdf', [ReportController::class, 'damagesPdf'])->name('reports.damages_pdf');
        Route::get('/reports/borrowings/pdf', [ReportController::class, 'borrowingsPdf'])->name('reports.borrowings_pdf');

        /**
         * ✅ Tahap 18 — Data Pengguna (Admin Users)
         */
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');

        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

        Route::patch('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset_password');
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle_status');

        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/restore', [AdminUserController::class, 'restore'])
            ->withTrashed()
            ->name('users.restore');

        // ✅ Backward compatible: link lama /admin/system/users diarahkan ke /admin/users
        Route::redirect('/system/users', '/admin/users')->name('system.users');

        /**
         * PLACEHOLDER pages (Tahap 19+ / lainnya)
         */
        Route::get('/system/settings', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Pengaturan']))
            ->name('system.settings');

        Route::get('/system/activity-logs', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Log Aktivitas']))
            ->name('system.logs');
    });

/**
 * PROFILE (bawaan Breeze)
 */
Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
