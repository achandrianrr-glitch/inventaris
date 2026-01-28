<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Models\Notification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = parent::share($request);

        $user = $request->user();

        // Props auth tetap ada seperti kode kamu
        $shared['auth'] = [
            'user' => $user,
        ];

        // Props notif global (muncul di semua halaman admin)
        if ($user) {
            $shared['unreadCount'] = Notification::query()
                ->where('admin_id', $user->id)
                ->where('is_read', false)
                ->count();

            $shared['notifications'] = Notification::query()
                ->where('admin_id', $user->id)
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'type', 'title', 'message', 'is_read', 'created_at']);
        } else {
            $shared['unreadCount'] = 0;
            $shared['notifications'] = [];
        }

        return $shared;
    }
}
