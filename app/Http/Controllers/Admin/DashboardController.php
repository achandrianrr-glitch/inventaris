<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Brand;
use App\Models\Damage;
use App\Models\Item;
use App\Models\Borrower;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $admin = $request->user();

        $kpi = [
            'item_types'      => Item::query()->count(),
            'units_total'     => (int) Item::query()->sum('stock_total'),
            'units_available' => (int) Item::query()->sum('stock_available'),
            'units_borrowed'  => (int) Item::query()->sum('stock_borrowed'),
            'units_damaged'   => (int) Item::query()->sum('stock_damaged'),
            'brands'          => Brand::query()->count(),
            'borrowers'       => Borrower::query()->count(),
            'low_stock'       => Item::query()->where('stock_available', '<', 5)->count(),
        ];

        $latestItems = Item::query()
            ->select(['id', 'code', 'name', 'category_id', 'brand_id', 'location_id', 'stock_available', 'status', 'created_at'])
            ->with([
                'category:id,name',
                'brand:id,name',
                'location:id,name',
            ])
            ->latest('created_at')
            ->limit(6)
            ->get();

        $latestDamages = Damage::query()
            ->select(['id', 'code', 'item_id', 'damage_level', 'status', 'reported_date', 'admin_id'])
            ->with([
                'item:id,code,name',
                'admin:id,name',
            ])
            ->latest('reported_date')
            ->limit(6)
            ->get();

        $dueSoon = Borrowing::query()
            ->select(['id', 'code', 'borrower_id', 'item_id', 'qty', 'return_due', 'status'])
            ->with([
                'borrower:id,name,type',
                'item:id,code,name',
            ])
            ->whereIn('status', ['borrowed', 'late'])
            ->orderBy('return_due', 'asc')
            ->limit(6)
            ->get()
            ->map(function ($b) {
                $b->is_overdue = now()->greaterThan($b->return_due);
                return $b;
            });

        $notifications = Notification::query()
            ->where('admin_id', $admin->id)
            ->latest('created_at')
            ->limit(8)
            ->get(['id', 'type', 'title', 'message', 'is_read', 'created_at']);

        $unreadCount = Notification::query()
            ->where('admin_id', $admin->id)
            ->where('is_read', false)
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'kpi' => $kpi,
            'latestItems' => $latestItems,
            'latestDamages' => $latestDamages,
            'dueSoon' => $dueSoon,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
}
