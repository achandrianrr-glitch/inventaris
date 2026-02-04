<?php

namespace App\Exports;

use App\Exports\Sheets\BorrowingsSheet;
use App\Exports\Sheets\DamagesSheet;
use App\Exports\Sheets\InventorySheet;
use App\Exports\Sheets\TransactionsInSheet;
use App\Exports\Sheets\TransactionsOutSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportsWorkbookExport implements WithMultipleSheets
{
    public function __construct(private array $filters = []) {}

    public function sheets(): array
    {
        return [
            new InventorySheet($this->filters),
            new TransactionsInSheet($this->filters),
            new TransactionsOutSheet($this->filters),
            new DamagesSheet($this->filters),
            new BorrowingsSheet($this->filters),
        ];
    }
}
