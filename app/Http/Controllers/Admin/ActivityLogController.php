<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ActivityLogsExport;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'admin_id' => $request->query('admin_id', ''),
            'module' => $request->query('module', ''),
            'action' => $request->query('action', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'search' => trim((string)$request->query('search', '')),
        ];

        $q = ActivityLog::query()
            ->with(['admin:id,name,email'])
            ->select(['id', 'admin_id', 'module', 'action', 'description', 'ip_address', 'user_agent', 'created_at']);

        if ($filters['admin_id'] !== '') $q->where('admin_id', (int)$filters['admin_id']);
        if ($filters['module'] !== '') $q->where('module', $filters['module']);
        if ($filters['action'] !== '') $q->where('action', $filters['action']);
        if ($filters['date_from'] !== '') $q->whereDate('created_at', '>=', $filters['date_from']);
        if ($filters['date_to'] !== '') $q->whereDate('created_at', '<=', $filters['date_to']);

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $q->where(function (Builder $x) use ($s) {
                $x->where('description', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%")
                    ->orWhere('module', 'like', "%{$s}%")
                    ->orWhere('action', 'like', "%{$s}%");
            });
        }

        $logs = $q->orderByDesc('id')->paginate(15)->withQueryString();

        $admins = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'admins' => $admins,
            'filters' => $filters,
            'modules' => $this->moduleOptions(),
            'actions' => ['create', 'update', 'delete', 'restore', 'reset', 'toggle', 'export', 'read', 'login', 'logout'],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filters = [
            'admin_id' => $request->query('admin_id', ''),
            'module' => $request->query('module', ''),
            'action' => $request->query('action', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'search' => trim((string)$request->query('search', '')),
        ];

        activity_log('activity_logs', 'export', 'Export log aktivitas (excel)');

        return Excel::download(new ActivityLogsExport($filters), 'Log_Aktivitas_' . date('Ymd_His') . '.xlsx');
    }

    private function moduleOptions(): array
    {
        return [
            'dashboard',
            'categories',
            'brands',
            'locations',
            'borrowers',
            'items',
            'transactions',
            'borrowings',
            'returns',
            'damages',
            'stock_opnames',
            'notifications',
            'reports',
            'users',
            'settings',
            'activity_logs',
        ];
    }
}
