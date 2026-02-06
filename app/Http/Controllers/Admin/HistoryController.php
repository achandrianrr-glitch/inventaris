<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Damage;
use App\Models\StockOpname;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function index(Request $request): Response
    {
        // =============================
        // Filters (utama: period/type/search/per_page)
        // NOTE: date_from/date_to tetap disupport untuk kompatibilitas UI lama
        // =============================
        $filters = [
            'period'    => (string) $request->query('period', '30d'), // 7d|30d|90d
            'type'      => (string) $request->query('type', 'all'),   // all|in|out|borrow|return|damage|opname
            'search'    => trim((string) $request->query('search', '')),
            'per_page'  => (int) $request->query('per_page', 15),

            // backward compatible
            'date_from' => (string) $request->query('date_from', ''), // YYYY-MM-DD
            'date_to'   => (string) $request->query('date_to', ''),   // YYYY-MM-DD
        ];

        $perPage = max(5, min(50, (int) $filters['per_page']));
        $page    = max(1, (int) $request->query('page', 1));

        // Resolve range:
        // - jika date_from/date_to ada => custom range
        // - kalau tidak => pakai period (default 30 hari)
        [$from, $to] = $this->resolveDateRange($filters['period'], $filters['date_from'], $filters['date_to']);

        $type   = $filters['type'];
        $search = $filters['search'];

        $entries = collect();

        // =========================================================
        // TRANSACTIONS (IN/OUT)
        // =========================================================
        if (in_array($type, ['all', 'in', 'out'], true)) {
            $trxQ = Transaction::query()
                ->select([
                    'id',
                    'code',
                    'type',
                    'item_id',
                    'qty',
                    'from_location',
                    'to_location',
                    'transaction_date',
                    'status',
                    'notes',
                    'created_at',
                ])
                ->with(['item:id,code,name'])
                ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
                ->latest('transaction_date')
                ->limit(800);

            if ($type !== 'all') {
                $trxQ->where('type', $type);
            }

            foreach ($trxQ->get() as $t) {
                $at = $t->created_at
                    ? Carbon::parse($t->created_at)
                    : ($t->transaction_date ? Carbon::parse($t->transaction_date)->startOfDay() : now());

                $label  = $t->type === 'in' ? 'Barang Masuk' : 'Barang Keluar';
                $detail = $t->type === 'in'
                    ? ('Dari: ' . ($t->from_location ?: '-'))
                    : ('Ke: ' . ($t->to_location ?: '-'));

                if (!empty($t->notes)) {
                    $detail .= ' • Catatan: ' . $t->notes;
                }

                $entries->push([
                    'key'    => 'trx-' . $t->type . '-' . $t->id,
                    'type'   => $t->type, // in|out
                    'label'  => $label,
                    'code'   => $t->code,
                    'qty'    => (int) $t->qty,
                    'item'   => [
                        'id'   => $t->item?->id,
                        'code' => $t->item?->code,
                        'name' => $t->item?->name,
                    ],
                    'status' => $t->status,
                    'detail' => $detail,
                    'at'     => $at->toISOString(),
                ]);
            }
        }

        // =========================================================
        // BORROWINGS (BORROW + RETURN)
        // =========================================================
        if (in_array($type, ['all', 'borrow', 'return'], true)) {
            $borQ = Borrowing::query()
                ->select([
                    'id',
                    'code',
                    'borrower_id',
                    'item_id',
                    'qty',
                    'borrow_date',
                    'borrow_time',
                    'return_due',
                    'return_date',
                    'return_condition',
                    'status',
                    'created_at',
                ])
                ->with([
                    'item:id,code,name',
                    'borrower:id,name,type',
                ])
                ->where(function ($q) use ($from, $to, $type) {
                    if ($type === 'return') {
                        $q->whereNotNull('return_date')
                            ->whereBetween('return_date', [$from, $to]);
                        return;
                    }

                    if ($type === 'borrow') {
                        $q->whereBetween('borrow_date', [$from->toDateString(), $to->toDateString()]);
                        return;
                    }

                    // all
                    $q->whereBetween('borrow_date', [$from->toDateString(), $to->toDateString()])
                        ->orWhere(function ($qq) use ($from, $to) {
                            $qq->whereNotNull('return_date')
                                ->whereBetween('return_date', [$from, $to]);
                        });
                })
                ->latest('created_at')
                ->limit(800);

            foreach ($borQ->get() as $b) {
                // EVENT: BORROW (kecuali kalau filter return-only)
                if ($type !== 'return') {
                    $atBorrow = $this->resolveBorrowDateTime($b->borrow_date, $b->borrow_time, $b->created_at);

                    $entries->push([
                        'key'    => 'bor-borrow-' . $b->id,
                        'type'   => 'borrow',
                        'label'  => 'Peminjaman',
                        'code'   => $b->code,
                        'qty'    => (int) $b->qty,
                        'item'   => [
                            'id'   => $b->item?->id,
                            'code' => $b->item?->code,
                            'name' => $b->item?->name,
                        ],
                        'status' => $b->status,
                        'detail' => 'Peminjam: ' . ($b->borrower?->name ?: '-') .
                            ' • Due: ' . ($b->return_due ? Carbon::parse($b->return_due)->format('Y-m-d H:i') : '-'),
                        'at'     => $atBorrow->toISOString(),
                    ]);
                }

                // EVENT: RETURN (all atau return)
                if (($type === 'all' || $type === 'return') && $b->return_date) {
                    $atReturn = Carbon::parse($b->return_date);

                    $entries->push([
                        'key'    => 'bor-return-' . $b->id,
                        'type'   => 'return',
                        'label'  => 'Pengembalian',
                        'code'   => $b->code,
                        'qty'    => (int) $b->qty,
                        'item'   => [
                            'id'   => $b->item?->id,
                            'code' => $b->item?->code,
                            'name' => $b->item?->name,
                        ],
                        'status' => $b->return_condition ?: $b->status,
                        'detail' => 'Peminjam: ' . ($b->borrower?->name ?: '-') .
                            ' • Kondisi: ' . ($b->return_condition ?: '-'),
                        'at'     => $atReturn->toISOString(),
                    ]);
                }
            }
        }

        // =========================================================
        // DAMAGES
        // =========================================================
        if (in_array($type, ['all', 'damage'], true)) {
            $damQ = Damage::query()
                ->select(['id', 'code', 'item_id', 'damage_level', 'status', 'reported_date', 'created_at'])
                ->with(['item:id,code,name'])
                ->whereBetween('reported_date', [$from->toDateString(), $to->toDateString()])
                ->latest('reported_date')
                ->limit(800);

            foreach ($damQ->get() as $d) {
                $at = $d->created_at
                    ? Carbon::parse($d->created_at)
                    : ($d->reported_date ? Carbon::parse($d->reported_date)->startOfDay() : now());

                $entries->push([
                    'key'    => 'damage-' . $d->id,
                    'type'   => 'damage',
                    'label'  => 'Kerusakan',
                    'code'   => $d->code,
                    'qty'    => null,
                    'item'   => [
                        'id'   => $d->item?->id,
                        'code' => $d->item?->code,
                        'name' => $d->item?->name,
                    ],
                    'status' => $d->status,
                    'detail' => 'Level: ' . ($d->damage_level ?: '-') . ' • Status: ' . ($d->status ?: '-'),
                    'at'     => $at->toISOString(),
                ]);
            }
        }

        // =========================================================
        // STOCK OPNAMES
        // =========================================================
        if (in_array($type, ['all', 'opname'], true)) {
            $opQ = StockOpname::query()
                ->select([
                    'id',
                    'code',
                    'item_id',
                    'opname_date',
                    'system_stock',
                    'physical_stock',
                    'difference',
                    'status',
                    'validation',
                    'created_at',
                ])
                ->with(['item:id,code,name'])
                ->whereBetween('opname_date', [$from->toDateString(), $to->toDateString()])
                ->latest('opname_date')
                ->limit(800);

            foreach ($opQ->get() as $o) {
                $at = $o->created_at
                    ? Carbon::parse($o->created_at)
                    : ($o->opname_date ? Carbon::parse($o->opname_date)->startOfDay() : now());

                $entries->push([
                    'key'    => 'opname-' . $o->id,
                    'type'   => 'opname',
                    'label'  => 'Stock Opname',
                    'code'   => $o->code,
                    'qty'    => null,
                    'item'   => [
                        'id'   => $o->item?->id,
                        'code' => $o->item?->code,
                        'name' => $o->item?->name,
                    ],
                    'status' => $o->validation ?: $o->status,
                    'detail' => 'Sistem: ' . (int) $o->system_stock .
                        ' • Fisik: ' . (int) $o->physical_stock .
                        ' • Selisih: ' . (int) $o->difference,
                    'at'     => $at->toISOString(),
                ]);
            }
        }

        // =========================================================
        // SEARCH FILTER (code / item code / item name / label / detail / status)
        // =========================================================
        if ($search !== '') {
            $needle = mb_strtolower($search);

            $entries = $entries->filter(function ($e) use ($needle) {
                $hay = implode(' ', [
                    (string) ($e['code'] ?? ''),
                    (string) ($e['item']['code'] ?? ''),
                    (string) ($e['item']['name'] ?? ''),
                    (string) ($e['label'] ?? ''),
                    (string) ($e['detail'] ?? ''),
                    (string) ($e['status'] ?? ''),
                ]);

                return str_contains(mb_strtolower($hay), $needle);
            })->values();
        }

        // =========================================================
        // SORT (newest first)
        // =========================================================
        $entries = $entries->sortByDesc(fn($e) => $e['at'] ?? '')->values();

        // =========================================================
        // PAGINATION (manual)
        // =========================================================
        $total = $entries->count();
        $slice = $entries->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path'  => url('/admin/history'),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Admin/History/Index', [
            'history' => $paginator,

            // kirim filter yang dipakai UI
            'filters' => [
                'period'    => $filters['period'],
                'type'      => $filters['type'],
                'search'    => $filters['search'],
                'per_page'  => $perPage,

                // tetap kirim untuk UI lama (kalau ada)
                'date_from' => $filters['date_from'],
                'date_to'   => $filters['date_to'],
            ],

            // options untuk dropdown (kalau mau dipakai)
            'options' => [
                'periods' => [
                    ['value' => '7d',  'label' => '7 Hari'],
                    ['value' => '30d', 'label' => '30 Hari'],
                    ['value' => '90d', 'label' => '90 Hari'],
                ],
                'types' => [
                    ['value' => 'all',    'label' => 'Semua'],
                    ['value' => 'in',     'label' => 'Barang Masuk'],
                    ['value' => 'out',    'label' => 'Barang Keluar'],
                    ['value' => 'borrow', 'label' => 'Peminjaman'],
                    ['value' => 'return', 'label' => 'Pengembalian'],
                    ['value' => 'damage', 'label' => 'Kerusakan'],
                    ['value' => 'opname', 'label' => 'Stock Opname'],
                ],
            ],

            // meta range untuk ditampilkan di UI
            'meta' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
        ]);
    }

    /**
     * Resolve range:
     * - If date_from/date_to given (YYYY-MM-DD), they override period
     * - Else use period (7d|30d|90d), default 30d
     */
    private function resolveDateRange(string $period, string $dateFrom, string $dateTo): array
    {
        // custom override
        $from = null;
        $to   = null;

        if ($dateFrom !== '') {
            try {
                $from = Carbon::parse($dateFrom)->startOfDay();
            } catch (\Throwable $e) {
                $from = null;
            }
        }

        if ($dateTo !== '') {
            try {
                $to = Carbon::parse($dateTo)->endOfDay();
            } catch (\Throwable $e) {
                $to = null;
            }
        }

        if ($from || $to) {
            $from = $from ?: now()->subDays(29)->startOfDay();
            $to   = $to ?: now()->endOfDay();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to];
        }

        // period mode
        $days = match ($period) {
            '7d'  => 7,
            '90d' => 90,
            default => 30,
        };

        $from = now()->subDays($days - 1)->startOfDay();
        $to   = now()->endOfDay();

        return [$from, $to];
    }

    /**
     * Buat timestamp peminjaman:
     * - kalau ada borrow_date + borrow_time => gabung
     * - else fallback created_at atau startOfDay borrow_date
     */
    private function resolveBorrowDateTime($borrowDate, $borrowTime, $createdAt): Carbon
    {
        try {
            if (!empty($borrowDate)) {
                $base = Carbon::parse($borrowDate);

                if (!empty($borrowTime)) {
                    // borrow_time bisa "08:00" / "08:00:00"
                    $timeStr = (string) $borrowTime;
                    $timeStr = preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeStr) ? $timeStr : ($timeStr . ':00');
                    return Carbon::parse($base->toDateString() . ' ' . $timeStr);
                }

                return $createdAt ? Carbon::parse($createdAt) : $base->startOfDay();
            }

            return $createdAt ? Carbon::parse($createdAt) : now();
        } catch (\Throwable $e) {
            return $createdAt ? Carbon::parse($createdAt) : now();
        }
    }
}
