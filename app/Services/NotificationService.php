<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Create notification with simple dedupe (avoid spam).
     * Dedupe rule: same admin_id + type + reference_type + reference_id within last 24 hours.
     */
    public function createForAdmin(
        int $adminId,
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Notification {
        $referenceType = $referenceType ?: null;
        $referenceId = $referenceId ?: null;

        $exists = Notification::query()
            ->where('admin_id', $adminId)
            ->where('type', $type)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->exists();

        if ($exists) {
            // Return latest to keep caller simple
            return Notification::query()
                ->where('admin_id', $adminId)
                ->where('type', $type)
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->orderByDesc('id')
                ->first();
        }

        return Notification::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * For multiple admins (if later you add more admin accounts).
     */
    public function createForAllActiveAdmins(
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        $admins = User::query()->where('status', 'active')->get(['id']);
        foreach ($admins as $a) {
            $this->createForAdmin((int)$a->id, $type, $title, $message, $referenceType, $referenceId);
        }
    }
}
