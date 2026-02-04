<?php

namespace App\Exports\Sheets;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventorySheet implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Inventaris';
    }

    public function query()
    {
        $f = $this->filters;

        return Item::query()
            ->with(['category:id,name', 'brand:id,name', 'location:id,name'])
            ->when(!empty($f['category_id']), fn($q) => $q->where('category_id', (int)$f['category_id']))
            ->when(!empty($f['brand_id']), fn($q) => $q->where('brand_id', (int)$f['brand_id']))
            ->when(!empty($f['location_id']), fn($q) => $q->where('location_id', (int)$f['location_id']))
            ->when(!empty($f['status']), fn($q) => $q->where('status', $f['status']))
            ->orderBy('name')
            ->select([
                'id',
                'code',
                'name',
                'category_id',
                'brand_id',
                'location_id',
                'stock_total',
                'stock_available',
                'stock_borrowed',
                'stock_damaged',
                'condition',
                'status'
            ]);
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama',
            'Kategori',
            'Merek',
            'Lokasi',
            'Stok Total',
            'Stok Tersedia',
            'Dipinjam',
            'Rusak',
            'Kondisi',
            'Status'
        ];
    }

    public function map($item): array
    {
        return [
            $item->code,
            $item->name,
            $item->category?->name ?? '-',
            $item->brand?->name ?? '-',
            $item->location?->name ?? '-',
            (int)$item->stock_total,
            (int)$item->stock_available,
            (int)$item->stock_borrowed,
            (int)$item->stock_damaged,
            $item->condition,
            $item->status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
