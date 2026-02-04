<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $adminId = (int) $request->user()->id;

        $filters = [
            'status' => (string) $request->query('status', 'all'), // all | unread | read
            'type'   => (string) $request->query('type', 'all'),   // all | stock_low | overdue | damage | opname
            'search' => trim((string) $request->query('search', '')),
        ];

        $q = Notification::query()
            ->where('admin_id', $adminId)
            ->select([
                'id',
                'type',
                'title',
                'message',
                'reference_id',
                'reference_type',
                'is_read',
                'created_at',
            ]);

        // filter status
        if ($filters['status'] === 'unread') $q->where('is_read', false);
        if ($filters['status'] === 'read')   $q->where('is_read', true);

        // filter type (whitelist supaya aman)
        $allowedTypes = ['stock_low', 'overdue', 'damage', 'opname'];
        if ($filters['type'] !== 'all' && in_array($filters['type'], $allowedTypes, true)) {
            $q->where('type', $filters['type']);
        }

        // search
        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $q->where(function (Builder $x) use ($s) {
                $x->where('title', 'like', "%{$s}%")
                    ->orWhere('message', 'like', "%{$s}%");
            });
        }

        $notifications = $q
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $unreadCount = Notification::query()
            ->where('admin_id', $adminId)
            ->where('is_read', false)
            ->count();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $filters,
            'unreadCount' => $unreadCount,
        ]);
    }

    // untuk polling badge di topbar (axios GET)
    public function unreadCount(Request $request)
    {
        $adminId = (int) $request->user()->id;

        $count = Notification::query()
            ->where('admin_id', $adminId)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        // pastikan notifikasi milik admin yang login
        abort_if((int) $notification->admin_id !== (int) $request->user()->id, 403);

        if (!$notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('admin_id', (int) $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
