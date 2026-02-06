<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $f = $this->filters;

        $q = ActivityLog::query()
            ->with(['admin:id,name,email'])
            ->select(['id', 'admin_id', 'module', 'action', 'description', 'ip_address', 'user_agent', 'created_at']);

        if ($f['admin_id'] !== '') $q->where('admin_id', (int)$f['admin_id']);
        if ($f['module'] !== '') $q->where('module', $f['module']);
        if ($f['action'] !== '') $q->where('action', $f['action']);
        if ($f['date_from'] !== '') $q->whereDate('created_at', '>=', $f['date_from']);
        if ($f['date_to'] !== '') $q->whereDate('created_at', '<=', $f['date_to']);

        if (($f['search'] ?? '') !== '') {
            $s = $f['search'];
            $q->where(function (Builder $x) use ($s) {
                $x->where('description', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%")
                    ->orWhere('module', 'like', "%{$s}%")
                    ->orWhere('action', 'like', "%{$s}%");
            });
        }

        return $q->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['Waktu', 'Admin', 'Email', 'Module', 'Action', 'Description', 'IP', 'User Agent'];
    }

    public function map($l): array
    {
        return [
            (string)$l->created_at,
            $l->admin?->name ?? '-',
            $l->admin?->email ?? '-',
            $l->module,
            $l->action,
            $l->description ?? '',
            $l->ip_address ?? '',
            $l->user_agent ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
