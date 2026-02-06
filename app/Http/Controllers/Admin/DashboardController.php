<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Borrowing;
use App\Models\Brand;
use App\Models\Damage;
use App\Models\Item;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $admin = $request->user();
        if (!$admin) abort(403);

        // =========================
        // KPI
        // =========================
        $kpi = [
            'item_types'      => Item::query()->count(),
            'units_total'     => (int) Item::query()->sum('stock_total'),
            'units_available' => (int) Item::query()->sum('stock_available'),
            'units_borrowed'  => (int) Item::query()->sum('stock_borrowed'),
            'units_damaged'   => (int) Item::query()->sum('stock_damaged'),
            'brands'          => Brand::query()->count(),
            'borrowers'       => Borrower::query()->count(),
            'low_stock'       => Item::query()->where('stock_available', '<', 5)->count(),
        ];

        // =========================
        // Widget: Barang terbaru
        // (tetap dikirim biar aman walau panel dashboard bisa kamu ubah jadi history)
        // =========================
        $latestItems = Item::query()
            ->select([
                'id',
                'code',
                'name',
                'category_id',
                'brand_id',
                'location_id',
                'stock_available',
                'status',
                'created_at'
            ])
            ->with(['category:id,name', 'brand:id,name', 'location:id,name'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        // =========================
        // Widget: Kerusakan terbaru
        // =========================
        $latestDamages = Damage::query()
            ->select(['id', 'code', 'item_id', 'damage_level', 'status', 'reported_date', 'admin_id'])
            ->with(['item:id,code,name', 'admin:id,name'])
            ->latest('reported_date')
            ->limit(6)
            ->get();

        // =========================
        // Widget: Jatuh tempo / overdue
        // =========================
        $dueSoon = Borrowing::query()
            ->select(['id', 'code', 'borrower_id', 'item_id', 'qty', 'return_due', 'status'])
            ->with(['borrower:id,name,type', 'item:id,code,name'])
            ->whereIn('status', ['borrowed', 'late'])
            ->orderBy('return_due', 'asc')
            ->limit(6)
            ->get()
            ->map(function ($b) {
                $due = $b->return_due ? Carbon::parse($b->return_due) : null;
                $b->return_due_iso = $due ? $due->toISOString() : null;
                $b->is_overdue = $due ? now()->greaterThan($due) : false;
                return $b;
            });

        // =========================
        // ✅ Dashboard: Grafik statistik berdasarkan periode
        // Support: 7d, 30d, 90d, 180d, 365d
        // + from/to override
        // Output: labels + series (dan data untuk kompatibilitas)
        // =========================
        $period = (string) $request->query('period', '30d');
        [$from, $to] = $this->resolvePeriod($period, $request->query('from'), $request->query('to'));

        $labels = $this->dateLabels($from, $to);

        // series associative (key tanggal => nilai)
        $series = [
            'trx_in_qty'   => $this->zeroSeries($labels),
            'trx_out_qty'  => $this->zeroSeries($labels),
            'borrow_qty'   => $this->zeroSeries($labels),
            'return_qty'   => $this->zeroSeries($labels),
            'damage_count' => $this->zeroSeries($labels),
        ];

        // ---- Transactions in/out (qty)
        if (Schema::hasTable('transactions')) {
            $trxRows = DB::table('transactions')
                ->selectRaw("transaction_date as d, type, SUM(qty) as total_qty")
                ->whereDate('transaction_date', '>=', $from->toDateString())
                ->whereDate('transaction_date', '<=', $to->toDateString())
                ->groupBy('d', 'type')
                ->orderBy('d')
                ->get();

            foreach ($trxRows as $r) {
                $d = (string) $r->d;
                $qty = (int) $r->total_qty;

                if ($r->type === 'in' && array_key_exists($d, $series['trx_in_qty'])) {
                    $series['trx_in_qty'][$d] = $qty;
                }
                if ($r->type === 'out' && array_key_exists($d, $series['trx_out_qty'])) {
                    $series['trx_out_qty'][$d] = $qty;
                }
            }
        }

        // ---- Borrowings (qty)
        if (Schema::hasTable('borrowings')) {
            $borrowRows = DB::table('borrowings')
                ->selectRaw("borrow_date as d, SUM(qty) as total_qty")
                ->whereDate('borrow_date', '>=', $from->toDateString())
                ->whereDate('borrow_date', '<=', $to->toDateString())
                ->groupBy('d')
                ->orderBy('d')
                ->get();

            foreach ($borrowRows as $r) {
                $d = (string) $r->d;
                $qty = (int) $r->total_qty;
                if (array_key_exists($d, $series['borrow_qty'])) {
                    $series['borrow_qty'][$d] = $qty;
                }
            }

            // ---- Returns (qty) berdasarkan return_date
            // gunakan DATE(return_date) supaya group harian konsisten
            $returnRows = DB::table('borrowings')
                ->selectRaw("DATE(return_date) as d, SUM(qty) as total_qty")
                ->whereNotNull('return_date')
                ->whereDate('return_date', '>=', $from->toDateString())
                ->whereDate('return_date', '<=', $to->toDateString())
                ->groupByRaw('DATE(return_date)')
                ->orderByRaw('DATE(return_date)')
                ->get();

            foreach ($returnRows as $r) {
                $d = (string) $r->d;
                $qty = (int) $r->total_qty;
                if (array_key_exists($d, $series['return_qty'])) {
                    $series['return_qty'][$d] = $qty;
                }
            }
        }

        // ---- Damages (count)
        if (Schema::hasTable('damages')) {
            $damageRows = DB::table('damages')
                ->selectRaw("reported_date as d, COUNT(*) as total_count")
                ->whereDate('reported_date', '>=', $from->toDateString())
                ->whereDate('reported_date', '<=', $to->toDateString())
                ->groupBy('d')
                ->orderBy('d')
                ->get();

            foreach ($damageRows as $r) {
                $d = (string) $r->d;
                $cnt = (int) $r->total_count;
                if (array_key_exists($d, $series['damage_count'])) {
                    $series['damage_count'][$d] = $cnt;
                }
            }
        }

        $chart = [
            'period' => $period,
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'labels' => array_values($labels),

            // ✅ untuk frontend baru (dashboard.vue versi saya)
            'series' => [
                'transactions_in'  => array_values($series['trx_in_qty']),
                'transactions_out' => array_values($series['trx_out_qty']),
                'borrowings'       => array_values($series['borrow_qty']),
                'returns'          => array_values($series['return_qty']),
                'damages'          => array_values($series['damage_count']),
            ],

            // ✅ untuk frontend kamu yg lama (kalau masih pakai chart.data.*)
            'data'   => [
                'trx_in_qty'   => array_values($series['trx_in_qty']),
                'trx_out_qty'  => array_values($series['trx_out_qty']),
                'borrow_qty'   => array_values($series['borrow_qty']),
                'return_qty'   => array_values($series['return_qty']),
                'damage_count' => array_values($series['damage_count']),
            ],
        ];

        // =========================
        // ✅ Dashboard: History (1 bulan terakhir)
        // Gabungan: transactions + borrowings (borrow & return) + damages + stock opnames (jika ada table)
        // Dashboard kirim 12, frontend bisa tampilkan 3 row
        // =========================
        $histFrom = now()->subMonth()->startOfDay();
        $histTo   = now()->endOfDay();

        $history = collect();

        // ---- 1) Transactions
        if (Schema::hasTable('transactions') && Schema::hasTable('items')) {
            $trx = DB::table('transactions as t')
                ->join('items as i', 't.item_id', '=', 'i.id')
                ->whereDate('t.transaction_date', '>=', $histFrom->toDateString())
                ->whereDate('t.transaction_date', '<=', $histTo->toDateString())
                ->orderByDesc('t.transaction_date')
                ->orderByDesc('t.id')
                ->limit(25)
                ->get([
                    't.id',
                    't.code',
                    't.type',
                    't.qty',
                    't.from_location',
                    't.to_location',
                    't.transaction_date',
                    't.status',
                    't.created_at',
                    'i.id as item_id',
                    'i.code as item_code',
                    'i.name as item_name',
                ]);

            foreach ($trx as $t) {
                $at = $t->created_at
                    ? Carbon::parse($t->created_at)
                    : Carbon::parse($t->transaction_date)->startOfDay();

                $history->push([
                    'key'   => 'trx-' . $t->type . '-' . $t->id,
                    'type'  => (string) $t->type, // in|out
                    'label' => ((string) $t->type === 'in') ? 'Barang Masuk' : 'Barang Keluar',
                    'code'  => (string) $t->code,
                    'qty'   => (int) $t->qty,
                    'item'  => [
                        'id'   => (int) $t->item_id,
                        'code' => (string) $t->item_code,
                        'name' => (string) $t->item_name,
                    ],
                    'status' => $t->status ?? null,
                    'detail' => ((string) $t->type === 'in')
                        ? ('Dari: ' . ($t->from_location ?: '-'))
                        : ('Ke: ' . ($t->to_location ?: '-')),
                    'at' => $at->toISOString(),
                ]);
            }
        }

        // ---- 2) Borrowings (event borrow + return)
        if (Schema::hasTable('borrowings') && Schema::hasTable('items') && Schema::hasTable('borrowers')) {
            $bor = DB::table('borrowings as b')
                ->join('items as i', 'b.item_id', '=', 'i.id')
                ->join('borrowers as br', 'b.borrower_id', '=', 'br.id')
                ->where(function ($q) use ($histFrom, $histTo) {
                    $q->whereBetween('b.borrow_date', [$histFrom->toDateString(), $histTo->toDateString()])
                        ->orWhere(function ($qq) use ($histFrom, $histTo) {
                            $qq->whereNotNull('b.return_date')
                                ->whereBetween(DB::raw('DATE(b.return_date)'), [$histFrom->toDateString(), $histTo->toDateString()]);
                        });
                })
                ->orderByDesc('b.created_at')
                ->limit(25)
                ->get([
                    'b.id',
                    'b.code',
                    'b.qty',
                    'b.borrow_date',
                    'b.return_due',
                    'b.return_date',
                    'b.return_condition',
                    'b.status',
                    'b.created_at',
                    'i.id as item_id',
                    'i.code as item_code',
                    'i.name as item_name',
                    'br.id as borrower_id',
                    'br.name as borrower_name',
                ]);

            foreach ($bor as $b) {
                // event: borrow
                $atBorrow = $b->created_at
                    ? Carbon::parse($b->created_at)
                    : ($b->borrow_date ? Carbon::parse($b->borrow_date)->startOfDay() : now());

                $history->push([
                    'key'   => 'bor-borrow-' . $b->id,
                    'type'  => 'borrow',
                    'label' => 'Peminjaman',
                    'code'  => (string) $b->code,
                    'qty'   => (int) $b->qty,
                    'item'  => [
                        'id'   => (int) $b->item_id,
                        'code' => (string) $b->item_code,
                        'name' => (string) $b->item_name,
                    ],
                    'status' => $b->status ?? null,
                    'detail' => 'Peminjam: ' . ($b->borrower_name ?: '-') . ' • Due: ' . ($b->return_due ? Carbon::parse($b->return_due)->format('Y-m-d H:i') : '-'),
                    'at' => $atBorrow->toISOString(),
                ]);

                // event: return (kalau ada)
                if (!empty($b->return_date)) {
                    $atReturn = Carbon::parse($b->return_date);

                    $history->push([
                        'key'   => 'bor-return-' . $b->id,
                        'type'  => 'return',
                        'label' => 'Pengembalian',
                        'code'  => (string) $b->code,
                        'qty'   => (int) $b->qty,
                        'item'  => [
                            'id'   => (int) $b->item_id,
                            'code' => (string) $b->item_code,
                            'name' => (string) $b->item_name,
                        ],
                        'status' => $b->return_condition ?: ($b->status ?? null),
                        'detail' => 'Peminjam: ' . ($b->borrower_name ?: '-') . ' • Kondisi: ' . ($b->return_condition ?: '-'),
                        'at' => $atReturn->toISOString(),
                    ]);
                }
            }
        }

        // ---- 3) Damages
        if (Schema::hasTable('damages') && Schema::hasTable('items')) {
            $dam = DB::table('damages as d')
                ->join('items as i', 'd.item_id', '=', 'i.id')
                ->whereDate('d.reported_date', '>=', $histFrom->toDateString())
                ->whereDate('d.reported_date', '<=', $histTo->toDateString())
                ->orderByDesc('d.reported_date')
                ->orderByDesc('d.id')
                ->limit(20)
                ->get([
                    'd.id',
                    'd.code',
                    'd.item_id',
                    'd.damage_level',
                    'd.status',
                    'd.reported_date',
                    'd.created_at',
                    'i.code as item_code',
                    'i.name as item_name',
                ]);

            foreach ($dam as $d) {
                $at = $d->created_at
                    ? Carbon::parse($d->created_at)
                    : Carbon::parse($d->reported_date)->startOfDay();

                $history->push([
                    'key'   => 'damage-' . $d->id,
                    'type'  => 'damage',
                    'label' => 'Kerusakan',
                    'code'  => (string) $d->code,
                    'qty'   => null,
                    'item'  => [
                        'id'   => (int) $d->item_id,
                        'code' => (string) $d->item_code,
                        'name' => (string) $d->item_name,
                    ],
                    'status' => $d->status ?? null,
                    'detail' => 'Level: ' . ($d->damage_level ?: '-') . ' • Status: ' . ($d->status ?: '-'),
                    'at' => $at->toISOString(),
                ]);
            }
        }

        // ---- 4) Stock Opname (optional - hanya kalau table ada)
        if (Schema::hasTable('stock_opnames') && Schema::hasTable('items')) {
            $op = DB::table('stock_opnames as o')
                ->join('items as i', 'o.item_id', '=', 'i.id')
                ->whereDate('o.opname_date', '>=', $histFrom->toDateString())
                ->whereDate('o.opname_date', '<=', $histTo->toDateString())
                ->orderByDesc('o.opname_date')
                ->orderByDesc('o.id')
                ->limit(20)
                ->get([
                    'o.id',
                    'o.code',
                    'o.item_id',
                    'o.opname_date',
                    'o.system_stock',
                    'o.physical_stock',
                    'o.difference',
                    'o.status',
                    'o.validation',
                    'o.created_at',
                    'i.code as item_code',
                    'i.name as item_name',
                ]);

            foreach ($op as $o) {
                $at = $o->created_at
                    ? Carbon::parse($o->created_at)
                    : Carbon::parse($o->opname_date)->startOfDay();

                $history->push([
                    'key'   => 'opname-' . $o->id,
                    'type'  => 'opname',
                    'label' => 'Stock Opname',
                    'code'  => (string) $o->code,
                    'qty'   => null,
                    'item'  => [
                        'id'   => (int) $o->item_id,
                        'code' => (string) $o->item_code,
                        'name' => (string) $o->item_name,
                    ],
                    'status' => $o->validation ?: ($o->status ?? null),
                    'detail' => 'Sistem: ' . (int) $o->system_stock
                        . ' • Fisik: ' . (int) $o->physical_stock
                        . ' • Selisih: ' . (int) $o->difference,
                    'at' => $at->toISOString(),
                ]);
            }
        }

        $recentHistory = $history
            ->sortByDesc(fn($e) => $e['at'] ?? '')
            ->values()
            ->take(12)
            ->values();

        // =========================
        // Notifications (tetap dikirim agar frontend tidak rusak)
        // =========================
        $notifications = Notification::query()
            ->where('admin_id', $admin->id)
            ->latest('created_at')
            ->limit(8)
            ->get(['id', 'type', 'title', 'message', 'is_read', 'created_at']);

        $unreadCount = Notification::query()
            ->where('admin_id', $admin->id)
            ->where('is_read', false)
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'kpi' => $kpi,
            'latestItems' => $latestItems,
            'latestDamages' => $latestDamages,
            'dueSoon' => $dueSoon,

            // ✅ chart periode
            'chart' => $chart,

            // ✅ history gabungan
            'recentHistory' => $recentHistory,
            'historyMeta' => [
                'from' => $histFrom->toDateString(),
                'to' => $histTo->toDateString(),
            ],

            // ✅ biar aman kalau frontend kamu masih pakai ini (optional)
            'historyFrom' => $histFrom->toDateString(),

            // notif
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Resolve periode grafik.
     * - Default: 30d
     * - Support period: 7d, 30d, 90d, 180d, 365d
     * - Bisa override pakai query from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    private function resolvePeriod(string $period, ?string $fromQ, ?string $toQ): array
    {
        if ($fromQ && $toQ) {
            $from = Carbon::parse($fromQ)->startOfDay();
            $to   = Carbon::parse($toQ)->startOfDay();
            if ($to->lessThan($from)) {
                [$from, $to] = [$to, $from];
            }
            return [$from, $to];
        }

        $days = match ($period) {
            '7d'   => 7,
            '30d'  => 30,
            '90d'  => 90,
            '180d' => 180,
            '365d' => 365,
            default => 30,
        };

        $to = now()->startOfDay();
        $from = (clone $to)->subDays($days - 1);

        return [$from, $to];
    }

    /**
     * Return array label tanggal YYYY-MM-DD dari from..to.
     */
    private function dateLabels(Carbon $from, Carbon $to): array
    {
        $labels = [];
        $cursor = $from->copy();

        while ($cursor->lessThanOrEqualTo($to)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $labels;
    }

    /**
     * Create associative series default 0 with key = date label.
     */
    private function zeroSeries(array $labels): array
    {
        $out = [];
        foreach ($labels as $d) $out[$d] = 0;
        return $out;
    }
}
