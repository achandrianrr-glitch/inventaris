<?php

namespace App\Exports;

use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ItemsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected array $filters
    ) {}

    public function query()
    {
        $q = Item::query()
            ->with(['category:id,name', 'brand:id,name', 'location:id,name']);

        // trashed
        $trashed = $this->filters['trashed'] ?? 'without';
        if ($trashed === 'with') $q->withTrashed();
        if ($trashed === 'only') $q->onlyTrashed();

        // search
        $search = trim((string)($this->filters['search'] ?? ''));
        if ($search !== '') {
            $q->where(function (Builder $x) use ($search) {
                $x->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('specification', 'like', "%{$search}%");
            });
        }

        foreach (['category_id', 'brand_id', 'location_id'] as $k) {
            if (!empty($this->filters[$k])) $q->where($k, (int)$this->filters[$k]);
        }

        foreach (['status', 'condition'] as $k) {
            if (!empty($this->filters[$k]) && $this->filters[$k] !== 'all') $q->where($k, $this->filters[$k]);
        }

        return $q->orderByDesc('updated_at');
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
            'Tersedia',
            'Dipinjam',
            'Rusak',
            'Kondisi',
            'Status',
            'Tahun',
            'Harga',
            'Dihapus?',
            'Updated'
        ];
    }

    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            $row->category?->name,
            $row->brand?->name,
            $row->location?->name,
            $row->stock_total,
            $row->stock_available,
            $row->stock_borrowed,
            $row->stock_damaged,
            $row->condition,
            $row->status,
            $row->purchase_year,
            $row->purchase_price,
            $row->deleted_at ? 'YA' : 'TIDAK',
            $row->updated_at?->toDateTimeString(),
        ];
    }
}
