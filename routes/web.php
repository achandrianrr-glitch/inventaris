<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * HOME
 * - Kalau sudah login: langsung lempar ke admin dashboard
 * - Kalau belum: tampilkan Welcome (bawaan Breeze) atau bisa kamu ganti nanti
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
 * - Kita ubah jadi redirect ke admin dashboard biar konsisten.
 * - Name tetap 'dashboard' (biar Breeze / redirect bawaan tetap aman).
 */
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

/**
 * ADMIN AREA (Placeholder)
 * - Ini route utama admin: /admin
 * - Dikunci: auth + verified + active
 * - Nanti di TAHAP 4 bisa ganti ke AdminDashboardController + layout admin beneran
 */
Route::middleware(['auth', 'verified', 'active'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Placeholder', [
                'title' => 'Admin Dashboard',
            ]);
        })->name('dashboard'); // full name: admin.dashboard
    });

/**
 * PROFILE (bawaan Breeze)
 * - Ikut dikunci juga dengan 'active' supaya akun inactive tidak bisa akses
 */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
