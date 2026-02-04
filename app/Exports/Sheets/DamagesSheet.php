<?php

namespace App\Exports\Sheets;

use App\Models\Damage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DamagesSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Kerusakan';
    }

    public function query()
    {
        $f = $this->filters;

        return Damage::query()
            ->with(['item:id,code,name', 'admin:id,name'])
            ->when(!empty($f['damage_level']), fn($q) => $q->where('damage_level', $f['damage_level']))
            ->when(!empty($f['damage_status']), fn($q) => $q->where('status', $f['damage_status']))
            ->when(!empty($f['date_from']), fn($q) => $q->whereDate('reported_date', '>=', $f['date_from']))
            ->when(!empty($f['date_to']), fn($q) => $q->whereDate('reported_date', '<=', $f['date_to']))
            ->orderByDesc('reported_date')
            ->select([
                'id',
                'code',
                'item_id',
                'damage_level',
                'description',
                'reported_date',
                'status',
                'solution',
                'completion_date',
                'admin_id'
            ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Kode', 'Barang', 'Level', 'Status', 'Keluhan', 'Solusi', 'Selesai', 'Admin'];
    }

    public function map($d): array
    {
        $barang = ($d->item?->code ?? '-') . ' — ' . ($d->item?->name ?? '-');
        return [
            $d->reported_date,
            $d->code,
            $barang,
            $d->damage_level,
            $d->status,
            $d->description,
            $d->solution ?? '',
            $d->completion_date ?? '',
            $d->admin?->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
