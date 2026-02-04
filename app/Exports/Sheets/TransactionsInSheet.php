<?php

namespace App\Exports\Sheets;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsInSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Transaksi Masuk';
    }

    public function query()
    {
        $f = $this->filters;

        return Transaction::query()
            ->with(['item:id,code,name', 'admin:id,name'])
            ->where('type', 'in')
            ->when(!empty($f['date_from']), fn($q) => $q->whereDate('transaction_date', '>=', $f['date_from']))
            ->when(!empty($f['date_to']), fn($q) => $q->whereDate('transaction_date', '<=', $f['date_to']))
            ->orderByDesc('transaction_date')
            ->select([
                'id',
                'code',
                'type',
                'item_id',
                'qty',
                'from_location',
                'to_location',
                'transaction_date',
                'admin_id',
                'notes'
            ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Jenis', 'Kode', 'Barang', 'Dari', 'Kepada', 'Qty', 'Admin', 'Catatan'];
    }

    public function map($t): array
    {
        $barang = ($t->item?->code ?? '-') . ' — ' . ($t->item?->name ?? '-');
        return [
            $t->transaction_date,
            $t->type,
            $t->code,
            $barang,
            $t->from_location ?? '-',
            $t->to_location ?? '-',
            (int)$t->qty,
            $t->admin?->name ?? '-',
            $t->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
