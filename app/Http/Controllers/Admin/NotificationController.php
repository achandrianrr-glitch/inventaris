<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        // pastikan notifikasi milik admin yang login
        abort_if($notification->admin_id !== $request->user()->id, 403);

        $notification->update(['is_read' => true]);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('admin_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back();
    }
}
