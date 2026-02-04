<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportsWorkbookExport;
use App\Exports\Sheets\BorrowingsSheet;
use App\Exports\Sheets\DamagesSheet;
use App\Exports\Sheets\InventorySheet;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Borrowing;
use App\Models\Damage;
use App\Models\Item;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Normalisasi + validasi ringan filter query.
     * NOTE: keys ini dipakai juga untuk export Excel/PDF supaya konsisten.
     */
    private function filters(Request $r): array
    {
        $tab = $this->onlyIn($r->query('tab'), ['inventory', 'transactions', 'damages', 'borrowings']) ?? 'inventory';

        return [
            // tab & search (untuk tampilan web)
            'tab' => $tab,
            'q' => $this->cleanString($r->query('q')),

            // inventory filters
            'category_id' => $this->toIntOrNull($r->query('category_id')),
            'brand_id' => $this->toIntOrNull($r->query('brand_id')),
            'location_id' => $this->toIntOrNull($r->query('location_id')),
            'status' => $this->onlyIn($r->query('status'), ['active', 'service', 'inactive']),

            // date filters (transactions/damages/borrowings)
            'date_from' => $this->onlyDate($r->query('date_from')),
            'date_to' => $this->onlyDate($r->query('date_to')),

            // transactions filter (opsional, untuk tampilan web)
            'type' => $this->onlyIn($r->query('type'), ['in', 'out']),

            // damages filters
            'damage_level' => $this->onlyIn($r->query('damage_level'), ['minor', 'moderate', 'heavy']),
            'damage_status' => $this->onlyIn($r->query('damage_status'), ['pending', 'in_progress', 'completed']),

            // borrowings filters (kunci kamu: borrowing_status)
            'borrowing_status' => $this->onlyIn($r->query('borrowing_status'), ['borrowed', 'returned', 'late', 'damaged', 'lost']),
        ];
    }

    /**
     * ✅ HALAMAN WEB LAPORAN (Inertia)
     * Menampilkan data + summary + pagination sesuai tab.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $tab = $filters['tab'] ?? 'inventory';

        // Dropdown options filter
        $options = [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
        ];

        $summary = [];
        $data = null;

        if ($tab === 'inventory') {
            $query = Item::query()
                ->with(['category:id,name', 'brand:id,name', 'location:id,name'])
                ->when(!empty($filters['q']), function ($q) use ($filters) {
                    $keyword = $filters['q'];
                    $q->where(function ($w) use ($keyword) {
                        $w->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%");
                    });
                })
                ->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', (int) $filters['category_id']))
                ->when(!empty($filters['brand_id']), fn($q) => $q->where('brand_id', (int) $filters['brand_id']))
                ->when(!empty($filters['location_id']), fn($q) => $q->where('location_id', (int) $filters['location_id']))
                ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
                ->orderBy('name');

            $summary = [
                'total_items' => (clone $query)->count(),
                'total_stock' => (clone $query)->sum('stock_total'),
                'available_stock' => (clone $query)->sum('stock_available'),
                'borrowed_stock' => (clone $query)->sum('stock_borrowed'),
                'damaged_stock' => (clone $query)->sum('stock_damaged'),
                'low_stock_count' => (clone $query)->where('stock_available', '<', 5)->count(),
            ];

            $data = $query->paginate(15)->withQueryString()->through(function (Item $item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'category' => $item->category?->name,
                    'brand' => $item->brand?->name,
                    'location' => $item->location?->name,
                    'stock_total' => (int) $item->stock_total,
                    'stock_available' => (int) $item->stock_available,
                    'stock_borrowed' => (int) $item->stock_borrowed,
                    'stock_damaged' => (int) $item->stock_damaged,
                    'status' => $item->status,
                ];
            });
        }

        if ($tab === 'transactions') {
            $query = Transaction::query()
                ->with(['item:id,code,name', 'admin:id,name'])
                ->when(!empty($filters['q']), function ($q) use ($filters) {
                    $keyword = $filters['q'];
                    $q->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('item', function ($i) use ($keyword) {
                            $i->where('code', 'like', "%{$keyword}%")
                                ->orWhere('name', 'like', "%{$keyword}%");
                        });
                })
                ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
                ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('transaction_date', '>=', $filters['date_from']))
                ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('transaction_date', '<=', $filters['date_to']))
                ->orderByDesc('transaction_date')
                ->orderByDesc('id');

            $summary = [
                'count' => (clone $query)->count(),
                'qty_in' => (clone $query)->where('type', 'in')->sum('qty'),
                'qty_out' => (clone $query)->where('type', 'out')->sum('qty'),
            ];

            $data = $query->paginate(15)->withQueryString()->through(function (Transaction $t) {
                return [
                    'id' => $t->id,
                    'code' => $t->code,
                    'type' => $t->type,
                    'transaction_date' => $t->transaction_date,
                    'qty' => (int) $t->qty,
                    'item' => [
                        'code' => $t->item?->code,
                        'name' => $t->item?->name,
                    ],
                    'admin' => $t->admin?->name,
                    'from_location' => $t->from_location,
                    'to_location' => $t->to_location,
                    'notes' => $t->notes,
                ];
            });
        }

        if ($tab === 'damages') {
            $query = Damage::query()
                ->with(['item:id,code,name', 'admin:id,name'])
                ->when(!empty($filters['q']), function ($q) use ($filters) {
                    $keyword = $filters['q'];
                    $q->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('item', function ($i) use ($keyword) {
                            $i->where('code', 'like', "%{$keyword}%")
                                ->orWhere('name', 'like', "%{$keyword}%");
                        });
                })
                ->when(!empty($filters['damage_level']), fn($q) => $q->where('damage_level', $filters['damage_level']))
                ->when(!empty($filters['damage_status']), fn($q) => $q->where('status', $filters['damage_status']))
                ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('reported_date', '>=', $filters['date_from']))
                ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('reported_date', '<=', $filters['date_to']))
                ->orderByDesc('reported_date')
                ->orderByDesc('id');

            $summary = [
                'count' => (clone $query)->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
            ];

            $data = $query->paginate(15)->withQueryString()->through(function (Damage $d) {
                return [
                    'id' => $d->id,
                    'code' => $d->code,
                    'reported_date' => $d->reported_date,
                    'damage_level' => $d->damage_level,
                    'status' => $d->status,
                    'item' => [
                        'code' => $d->item?->code,
                        'name' => $d->item?->name,
                    ],
                    'admin' => $d->admin?->name,
                    'description' => $d->description,
                    'solution' => $d->solution,
                ];
            });
        }

        if ($tab === 'borrowings') {
            $query = Borrowing::query()
                ->with(['borrower:id,name,type', 'item:id,code,name', 'admin:id,name'])
                ->when(!empty($filters['q']), function ($q) use ($filters) {
                    $keyword = $filters['q'];
                    $q->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('borrower', fn($b) => $b->where('name', 'like', "%{$keyword}%"))
                        ->orWhereHas('item', function ($i) use ($keyword) {
                            $i->where('code', 'like', "%{$keyword}%")
                                ->orWhere('name', 'like', "%{$keyword}%");
                        });
                })
                ->when(!empty($filters['borrowing_status']), fn($q) => $q->where('status', $filters['borrowing_status']))
                ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('borrow_date', '>=', $filters['date_from']))
                ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('borrow_date', '<=', $filters['date_to']))
                ->orderByDesc('borrow_date')
                ->orderByDesc('id');

            $summary = [
                'count' => (clone $query)->count(),
                'borrowed' => (clone $query)->where('status', 'borrowed')->count(),
                'late' => (clone $query)->where('status', 'late')->count(),
                'returned' => (clone $query)->where('status', 'returned')->count(),
            ];

            $data = $query->paginate(15)->withQueryString()->through(function (Borrowing $b) {
                return [
                    'id' => $b->id,
                    'code' => $b->code,
                    'borrow_date' => $b->borrow_date,
                    'qty' => (int) $b->qty,
                    'status' => $b->status,
                    'return_due' => $b->return_due,
                    'borrower' => [
                        'name' => $b->borrower?->name,
                        'type' => $b->borrower?->type,
                    ],
                    'item' => [
                        'code' => $b->item?->code,
                        'name' => $b->item?->name,
                    ],
                    'admin' => $b->admin?->name,
                ];
            });
        }

        return Inertia::render('Admin/Reports/Index', [
            'tab' => $tab,
            'options' => $options,
            'filters' => $filters,
            'summary' => $summary,
            'data' => $data,
        ]);
    }

    // ========== EXCEL (single) ==========
    public function inventoryExcel(Request $request)
    {
        $filters = $this->filters($request);
        $name = 'Laporan_Inventaris_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new InventorySheet($filters), $name);
    }

    public function transactionsExcel(Request $request)
    {
        $filters = $this->filters($request);
        $name = 'Laporan_Transaksi_' . date('Ymd_His') . '.xlsx';

        // NOTE: kamu sebelumnya pakai ReportsWorkbookExport untuk transaksi (multi-sheet).
        // Aku pertahankan supaya tidak merusak struktur export yang sudah kamu buat.
        return Excel::download(new ReportsWorkbookExport($filters), $name);
    }

    public function damagesExcel(Request $request)
    {
        $filters = $this->filters($request);
        $name = 'Laporan_Kerusakan_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new DamagesSheet($filters), $name);
    }

    public function borrowingsExcel(Request $request)
    {
        $filters = $this->filters($request);
        $name = 'Laporan_Peminjaman_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new BorrowingsSheet($filters), $name);
    }

    // ========== EXCEL (workbook multi-sheet full) ==========
    public function workbookExcel(Request $request)
    {
        $filters = $this->filters($request);
        $name = 'Laporan_Lengkap_MultiSheet_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new ReportsWorkbookExport($filters), $name);
    }

    // ========== PDF ==========
    private function school(): array
    {
        $s = Setting::query()->first();

        return [
            'school_name' => $s?->school_name ?? 'Sekolah',
            'city' => $s?->city ?? '-',
        ];
    }

    public function inventoryPdf(Request $request)
    {
        $filters = $this->filters($request);

        $items = Item::query()
            ->with(['category:id,name', 'brand:id,name', 'location:id,name'])
            ->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', (int) $filters['category_id']))
            ->when(!empty($filters['brand_id']), fn($q) => $q->where('brand_id', (int) $filters['brand_id']))
            ->when(!empty($filters['location_id']), fn($q) => $q->where('location_id', (int) $filters['location_id']))
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('reports.inventory', [
            'school' => $this->school(),
            'filters' => $filters,
            'items' => $items,
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Inventaris_' . date('Ymd_His') . '.pdf');
    }

    public function transactionsPdf(Request $request)
    {
        $filters = $this->filters($request);

        $q = Transaction::query()
            ->with(['item:id,code,name', 'admin:id,name'])
            ->when(!empty($filters['date_from']), fn($x) => $x->whereDate('transaction_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn($x) => $x->whereDate('transaction_date', '<=', $filters['date_to']))
            ->orderByDesc('transaction_date');

        $in = (clone $q)->where('type', 'in')->get();
        $out = (clone $q)->where('type', 'out')->get();

        $pdf = Pdf::loadView('reports.transactions', [
            'school' => $this->school(),
            'filters' => $filters,
            'in' => $in,
            'out' => $out,
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Transaksi_' . date('Ymd_His') . '.pdf');
    }

    public function damagesPdf(Request $request)
    {
        $filters = $this->filters($request);

        $damages = Damage::query()
            ->with(['item:id,code,name', 'admin:id,name'])
            ->when(!empty($filters['damage_level']), fn($q) => $q->where('damage_level', $filters['damage_level']))
            ->when(!empty($filters['damage_status']), fn($q) => $q->where('status', $filters['damage_status']))
            ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('reported_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('reported_date', '<=', $filters['date_to']))
            ->orderByDesc('reported_date')
            ->get();

        $pdf = Pdf::loadView('reports.damages', [
            'school' => $this->school(),
            'filters' => $filters,
            'damages' => $damages,
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Kerusakan_' . date('Ymd_His') . '.pdf');
    }

    public function borrowingsPdf(Request $request)
    {
        $filters = $this->filters($request);

        $borrowings = Borrowing::query()
            ->with(['borrower:id,name,type', 'item:id,code,name', 'admin:id,name'])
            ->when(!empty($filters['borrowing_status']), fn($q) => $q->where('status', $filters['borrowing_status']))
            ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('borrow_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('borrow_date', '<=', $filters['date_to']))
            ->orderByDesc('borrow_date')
            ->get();

        $pdf = Pdf::loadView('reports.borrowings', [
            'school' => $this->school(),
            'filters' => $filters,
            'borrowings' => $borrowings,
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Peminjaman_' . date('Ymd_His') . '.pdf');
    }

    // =========================
    // Helpers
    // =========================
    private function toIntOrNull($v): ?int
    {
        if ($v === null || $v === '' || !is_numeric($v)) return null;
        $i = (int) $v;
        return $i > 0 ? $i : null;
    }

    private function cleanString($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
        return $s === '' ? null : mb_substr($s, 0, 100);
    }

    private function onlyIn($v, array $allowed): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
        return in_array($s, $allowed, true) ? $s : null;
    }

    private function onlyDate($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);

        // format aman: YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return null;

        return $s;
    }
}
