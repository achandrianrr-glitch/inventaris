<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(
        int $adminId,
        string $module,
        string $action,
        ?string $description = null,
        ?Request $request = null
    ): void {
        $req = $request ?: request();

        ActivityLog::create([
            'admin_id' => $adminId,
            'module' => $module,           // contoh: 'categories','items','borrowings','users'
            'action' => $action,           // create|update|delete|restore|reset|toggle|export|login
            'description' => $description,
            'ip_address' => $req?->ip(),
            'user_agent' => substr((string)$req?->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }
}
