<?php

namespace App\Exports;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoriesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected string $search,
        protected string $status,
        protected string $trashed
    ) {}

    public function query()
    {
        $query = Category::query()->select(['id', 'name', 'description', 'status', 'created_at', 'updated_at', 'deleted_at']);

        if ($this->trashed === 'with') {
            $query->withTrashed();
        } elseif ($this->trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($this->search !== '') {
            $query->where(function (Builder $q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if (in_array($this->status, ['active', 'inactive'], true)) {
            $query->where('status', $this->status);
        }

        return $query->orderByDesc('updated_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Deskripsi',
            'Status',
            'Dihapus?',
            'Created At',
            'Updated At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->description,
            $row->status,
            $row->deleted_at ? 'YA' : 'TIDAK',
            $row->created_at?->toDateTimeString(),
            $row->updated_at?->toDateTimeString(),
        ];
    }
}
