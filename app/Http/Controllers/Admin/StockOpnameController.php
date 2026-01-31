<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockOpnameStoreRequest;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockOpname;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string)$request->query('search', '')),
            'location_id' => (string)$request->query('location_id', ''),
            'date_from' => (string)$request->query('date_from', ''),
            'date_to' => (string)$request->query('date_to', ''),
        ];

        $q = StockOpname::query()
            ->with([
                'item:id,code,name,location_id,stock_total',
                'item.location:id,name',
                'admin:id,name',
            ])
            ->select([
                'id',
                'code',
                'opname_date',
                'item_id',
                'system_stock',
                'physical_stock',
                'difference',
                'status',
                'validation',
                'admin_id',
                'notes',
                'created_at'
            ]);

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $q->where(function (Builder $x) use ($s) {
                $x->where('code', 'like', "%{$s}%")
                    ->orWhereHas('item', fn($iq) => $iq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        if ($filters['location_id'] !== '') {
            $loc = (int)$filters['location_id'];
            $q->whereHas('item', fn($iq) => $iq->where('location_id', $loc));
        }

        if ($filters['date_from'] !== '') $q->whereDate('opname_date', '>=', $filters['date_from']);
        if ($filters['date_to'] !== '') $q->whereDate('opname_date', '<=', $filters['date_to']);

        $opnames = $q->orderByDesc('opname_date')->orderByDesc('id')->paginate(10)->withQueryString();

        return Inertia::render('Admin/StockOpnames/Index', [
            'opnames' => $opnames,
            'filters' => $filters,
            'options' => [
                'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    // ajax: load items by location
    public function itemsByLocation(Request $request)
    {
        $locId = (int)$request->query('location_id');

        $items = Item::query()
            ->where('location_id', $locId)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'location_id', 'stock_total', 'stock_available', 'stock_borrowed', 'stock_damaged', 'status']);

        return response()->json($items);
    }

    private function generateCode(): string
    {
        // contoh: OPN260130-0001
        $prefix = 'OPN' . date('ymd') . '-';

        $last = StockOpname::query()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $num = (int) end($parts);
            $next = $num + 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function store(StockOpnameStoreRequest $request)
    {
        $data = $request->validated();
        $adminId = (int) auth()->id();
        $code = $this->generateCode();

        return DB::transaction(function () use ($data, $adminId, $code) {

            $locationId = (int)$data['location_id'];
            $opnameDate = Carbon::parse($data['opname_date'])->toDateString();
            $notes = $data['notes'] ?? null;

            foreach ($data['lines'] as $line) {
                $itemId = (int)$line['item_id'];
                $physical = (int)$line['physical_stock'];

                /** @var \App\Models\Item $item */
                $item = Item::lockForUpdate()->findOrFail($itemId);

                // pastikan item sesuai lokasi yang dipilih
                if ((int)$item->location_id !== $locationId) {
                    return back()->withErrors(['location_id' => 'Ada item yang tidak sesuai lokasi dipilih.']);
                }

                $system = (int)$item->stock_total; // stok sistem
                $diff = $physical - $system;

                $status = ($diff === 0) ? 'normal' : 'discrepancy';
                $validation = ($diff === 0) ? 'matched' : 'review';

                StockOpname::create([
                    'code' => $code,
                    'opname_date' => $opnameDate,
                    'item_id' => $item->id,
                    'system_stock' => $system,
                    'physical_stock' => $physical,
                    'difference' => $diff,
                    'status' => $status,
                    'validation' => $validation,
                    'admin_id' => $adminId,
                    'notes' => $notes,
                ]);
            }

            return back()->with('success', "Stock opname berhasil disimpan. Kode batch: {$code}");
        });
    }

    public function review(Request $request): Response
    {
        $filters = [
            'location_id' => (string)$request->query('location_id', ''),
            'date_from' => (string)$request->query('date_from', ''),
            'date_to' => (string)$request->query('date_to', ''),
        ];

        $q = StockOpname::query()
            ->where('status', 'discrepancy')
            ->where('validation', 'review')
            ->with([
                'item:id,code,name,location_id,stock_total,stock_borrowed,stock_damaged,stock_available',
                'item.location:id,name',
                'admin:id,name',
            ])
            ->select([
                'id',
                'code',
                'opname_date',
                'item_id',
                'system_stock',
                'physical_stock',
                'difference',
                'status',
                'validation',
                'admin_id',
                'notes',
                'created_at'
            ]);

        if ($filters['location_id'] !== '') {
            $loc = (int)$filters['location_id'];
            $q->whereHas('item', fn($iq) => $iq->where('location_id', $loc));
        }
        if ($filters['date_from'] !== '') $q->whereDate('opname_date', '>=', $filters['date_from']);
        if ($filters['date_to'] !== '') $q->whereDate('opname_date', '<=', $filters['date_to']);

        $reviewItems = $q->orderByDesc('opname_date')->orderByDesc('id')->paginate(10)->withQueryString();

        return Inertia::render('Admin/StockOpnames/Review', [
            'reviewItems' => $reviewItems,
            'filters' => $filters,
            'options' => [
                'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    // approval: apply adjustment to item stock_total + stock_available (aman)
    public function approve(StockOpname $stockOpname)
    {
        return DB::transaction(function () use ($stockOpname) {

            /** @var \App\Models\StockOpname $opn */
            $opn = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opn->validation === 'approved') {
                return back()->with('success', 'Sudah di-approve.');
            }

            /** @var \App\Models\Item $item */
            $item = Item::lockForUpdate()->findOrFail((int)$opn->item_id);

            // Validasi penting: stok fisik tidak boleh lebih kecil dari (borrowed + damaged)
            $minRequired = (int)$item->stock_borrowed + (int)$item->stock_damaged;
            if ((int)$opn->physical_stock < $minRequired) {
                return back()->withErrors([
                    'approve' => "Tidak bisa approve: stok fisik ({$opn->physical_stock}) < borrowed+damaged ({$minRequired}).",
                ]);
            }

            // Apply adjustment (menyamakan sistem ke fisik)
            $item->stock_total = (int)$opn->physical_stock;
            $item->stock_available = (int)$item->stock_total - (int)$item->stock_borrowed - (int)$item->stock_damaged;
            if ((int)$item->stock_available < 0) $item->stock_available = 0;
            $item->save();

            $opn->validation = 'approved';
            $opn->save();

            return back()->with('success', 'Opname discrepancy di-approve & stok sistem disesuaikan.');
        });
    }

    // export CSV sederhana (tanpa package)
    public function exportCsv(Request $request)
    {
        $loc = $request->query('location_id', '');
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        $q = StockOpname::query()
            ->with(['item:id,code,name,location_id', 'item.location:id,name'])
            ->orderByDesc('opname_date')->orderByDesc('id');

        if ($loc !== '') {
            $locId = (int)$loc;
            $q->whereHas('item', fn($iq) => $iq->where('location_id', $locId));
        }
        if ($dateFrom !== '') $q->whereDate('opname_date', '>=', $dateFrom);
        if ($dateTo !== '') $q->whereDate('opname_date', '<=', $dateTo);

        $rows = $q->get();

        $filename = 'stock_opname_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['code', 'opname_date', 'location', 'item_code', 'item_name', 'system_stock', 'physical_stock', 'difference', 'status', 'validation']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->code,
                    $r->opname_date,
                    $r->item?->location?->name ?? '-',
                    $r->item?->code ?? '-',
                    $r->item?->name ?? '-',
                    $r->system_stock,
                    $r->physical_stock,
                    $r->difference,
                    $r->status,
                    $r->validation,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
