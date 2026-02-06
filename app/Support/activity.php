<?php

use App\Services\ActivityLogger;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

if (!function_exists('activity_log')) {
    /**
     * Global helper untuk mencatat aktivitas admin.
     *
     * Aman dipanggil dari:
     * - HTTP request biasa
     * - CLI/queue/cron (request bisa kosong)
     *
     * Tidak akan melempar error ke user jika logger gagal.
     */
    function activity_log(string $module, string $action, ?string $description = null): void
    {
        try {
            // ✅ pakai Auth facade (lebih konsisten)
            $adminId = Auth::id();
            if (!$adminId) {
                return;
            }

            // ✅ ambil request kalau ada, kalau tidak (CLI) pakai request kosong
            $req = null;
            try {
                if (function_exists('app') && app()->bound('request')) {
                    $req = app('request');
                }
            } catch (\Throwable $e) {
                $req = null;
            }

            if (!$req instanceof HttpRequest) {
                $req = new HttpRequest();
            }

            // ✅ rapihkan description (hindari string kosong + batasi panjang)
            if (is_string($description)) {
                $description = trim($description);
                if ($description === '') {
                    $description = null;
                } else {
                    $max = 2000;
                    if (strlen($description) > $max) {
                        $description = substr($description, 0, $max) . '...';
                    }
                }
            }

            // ✅ eksekusi logger (kalau gagal jangan ganggu flow utama)
            app(ActivityLogger::class)->log(
                (int) $adminId,
                $module,
                $action,
                $description,
                $req
            );
        } catch (\Throwable $e) {
            // jangan sampai error logging mematikan proses utama
            try {
                if (function_exists('logger')) {
                    logger()->warning('activity_log failed', [
                        'error' => $e->getMessage(),
                        'module' => $module ?? null,
                        'action' => $action ?? null,
                    ]);
                }
            } catch (\Throwable $ignored) {
                // no-op
            }
        }
    }
}
