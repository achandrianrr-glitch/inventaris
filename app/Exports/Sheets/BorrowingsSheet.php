<?php

namespace App\Exports\Sheets;

use App\Models\Borrowing;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BorrowingsSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Peminjaman';
    }

    public function query()
    {
        $f = $this->filters;

        return Borrowing::query()
            ->with(['borrower:id,name,type', 'item:id,code,name'])
            ->when(!empty($f['borrowing_status']), fn($q) => $q->where('status', $f['borrowing_status']))
            ->when(!empty($f['date_from']), fn($q) => $q->whereDate('borrow_date', '>=', $f['date_from']))
            ->when(!empty($f['date_to']), fn($q) => $q->whereDate('borrow_date', '<=', $f['date_to']))
            ->orderByDesc('borrow_date')
            ->select([
                'id',
                'code',
                'borrower_id',
                'item_id',
                'qty',
                'borrow_type',
                'borrow_date',
                'return_due',
                'return_date',
                'status'
            ]);
    }

    public function headings(): array
    {
        return ['Kode', 'Peminjam', 'Tipe', 'Barang', 'Qty', 'Jenis', 'Tanggal Pinjam', 'Jatuh Tempo', 'Kembali', 'Status'];
    }

    public function map($b): array
    {
        $barang = ($b->item?->code ?? '-') . ' — ' . ($b->item?->name ?? '-');
        return [
            $b->code,
            $b->borrower?->name ?? '-',
            $b->borrower?->type ?? '-',
            $barang,
            (int)$b->qty,
            $b->borrow_type,
            $b->borrow_date,
            $b->return_due,
            $b->return_date ?? '',
            $b->status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
