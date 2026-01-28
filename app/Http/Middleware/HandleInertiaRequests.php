<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use Illuminate\Http\Request;
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
        $user = $request->user();

        return array_merge(parent::share($request), [
            // JANGAN override 'auth' manual.
            // parent::share() biasanya sudah menyertakan auth.user,
            // jadi kita cukup tambah props baru di sini.

            'unreadCount' => $user
                ? Notification::query()
                ->where('admin_id', $user->id)
                ->where('is_read', false)
                ->count()
                : 0,

            'notifications' => $user
                ? Notification::query()
                ->where('admin_id', $user->id)
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'type', 'title', 'message', 'is_read', 'created_at'])
                : [],
        ]);
    }
}
