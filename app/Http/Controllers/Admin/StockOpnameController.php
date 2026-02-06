<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockOpnameStoreRequest;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockOpname;
use App\Services\NotificationService;
use App\Support\ItemCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'created_at',
            ]);

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $normalizedItemCode = ItemCode::normalize3DigitDash4($s); // ✅ LAB0001 -> LAB-0001

            $q->where(function (Builder $x) use ($s, $normalizedItemCode) {
                // cari kode opname batch (OPN260129-0001, dll)
                $x->where('code', 'like', "%{$s}%")

                    // cari dari item (kode unik 3digit-0000 / nama / kode lama tetap kebaca)
                    ->orWhereHas('item', function (Builder $iq) use ($s, $normalizedItemCode) {
                        $iq->where(function (Builder $z) use ($s, $normalizedItemCode) {
                            if ($normalizedItemCode) {
                                $z->orWhere('code', $normalizedItemCode);
                            }

                            $z->orWhere('code', 'like', "%{$s}%")
                                ->orWhere('name', 'like', "%{$s}%");
                        });
                    });
            });
        }

        if ($filters['location_id'] !== '') {
            $loc = (int)$filters['location_id'];
            $q->whereHas('item', fn(Builder $iq) => $iq->where('location_id', $loc));
        }

        if ($filters['date_from'] !== '') $q->whereDate('opname_date', '>=', $filters['date_from']);
        if ($filters['date_to'] !== '') $q->whereDate('opname_date', '<=', $filters['date_to']);

        $opnames = $q->orderByDesc('opname_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/StockOpnames/Index', [
            'opnames' => $opnames,
            'filters' => $filters,
            'options' => [
                'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    // ajax: load items by location (+ optional search)
    public function itemsByLocation(Request $request)
    {
        $locId = (int)$request->query('location_id');
        $search = trim((string)$request->query('search', ''));

        $q = Item::query()
            ->where('location_id', $locId)
            ->orderBy('name');

        if ($search !== '') {
            $normalizedItemCode = ItemCode::normalize3DigitDash4($search);

            $q->where(function (Builder $x) use ($search, $normalizedItemCode) {
                if ($normalizedItemCode) {
                    $x->orWhere('code', $normalizedItemCode);
                }

                $x->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $items = $q->get([
            'id',
            'code',
            'name',
            'location_id',
            'stock_total',
            'stock_available',
            'stock_borrowed',
            'stock_damaged',
            'status',
        ]);

        return response()->json($items);
    }

    private function generateCode(): string
    {
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

        $adminId = (int) Auth::id();
        $code = $this->generateCode();

        $locationId = (int)$data['location_id'];
        $opnameDate = Carbon::parse($data['opname_date'])->toDateString();
        $notes = $data['notes'] ?? null;

        $locationName = Location::query()->whereKey($locationId)->value('name');
        $locationLabel = $locationName ? "{$locationName} (ID: {$locationId})" : "ID: {$locationId}";

        // ✅ VALIDASI AWAL: semua item harus milik lokasi yang dipilih
        $itemIds = array_map(fn($x) => (int)$x['item_id'], $data['lines']);
        $hasMismatch = Item::query()
            ->whereIn('id', $itemIds)
            ->where('location_id', '!=', $locationId)
            ->exists();

        if ($hasMismatch) {
            return back()->withErrors(['location_id' => 'Ada item yang tidak sesuai lokasi dipilih.']);
        }

        return DB::transaction(function () use ($data, $adminId, $code, $locationId, $opnameDate, $notes, $locationLabel) {
            $hasDiscrepancy = false;
            $batchReferenceId = null;

            $createdCount = 0;
            $discrepancyCount = 0;

            foreach ($data['lines'] as $line) {
                $itemId = (int)$line['item_id'];
                $physical = (int)$line['physical_stock'];

                /** @var \App\Models\Item $item */
                $item = Item::lockForUpdate()->findOrFail($itemId);

                $system = (int)$item->stock_total;
                $diff = $physical - $system;

                $status = ($diff === 0) ? 'normal' : 'discrepancy';
                $validation = ($diff === 0) ? 'matched' : 'review';

                if ($diff !== 0) {
                    $hasDiscrepancy = true;
                    $discrepancyCount++;
                }

                $created = StockOpname::create([
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

                $createdCount++;

                if ($batchReferenceId === null) {
                    $batchReferenceId = (int)$created->id;
                }
            }

            if ($hasDiscrepancy) {
                app(NotificationService::class)->createForAdmin(
                    $adminId,
                    'opname',
                    'Opname ada selisih',
                    "Terdapat selisih stock pada batch opname {$code}. Silakan buka menu Review Selisih.",
                    'stock_opname',
                    $batchReferenceId
                );
            }

            $notesShort = $notes ? mb_substr((string)$notes, 0, 80) : '-';
            activity_log(
                'stock_opnames',
                'create',
                "Buat stock opname batch {$code} | lokasi={$locationLabel} | tanggal={$opnameDate}"
                    . " | lines={$createdCount} | discrepancy={$discrepancyCount}"
                    . " | notif=" . ($hasDiscrepancy ? 'yes' : 'no')
                    . " | notes={$notesShort}"
            );

            return back()->with('success', "Stock opname berhasil disimpan. Kode batch: {$code}");
        });
    }

    public function review(Request $request): Response
    {
        $filters = [
            'search' => trim((string)$request->query('search', '')), // ✅ tambahan (aman walau UI belum pakai)
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
                'created_at',
            ]);

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $normalizedItemCode = ItemCode::normalize3DigitDash4($s);

            $q->where(function (Builder $x) use ($s, $normalizedItemCode) {
                $x->where('code', 'like', "%{$s}%")
                    ->orWhereHas('item', function (Builder $iq) use ($s, $normalizedItemCode) {
                        $iq->where(function (Builder $z) use ($s, $normalizedItemCode) {
                            if ($normalizedItemCode) {
                                $z->orWhere('code', $normalizedItemCode);
                            }

                            $z->orWhere('code', 'like', "%{$s}%")
                                ->orWhere('name', 'like', "%{$s}%");
                        });
                    });
            });
        }

        if ($filters['location_id'] !== '') {
            $loc = (int)$filters['location_id'];
            $q->whereHas('item', fn(Builder $iq) => $iq->where('location_id', $loc));
        }

        if ($filters['date_from'] !== '') $q->whereDate('opname_date', '>=', $filters['date_from']);
        if ($filters['date_to'] !== '') $q->whereDate('opname_date', '<=', $filters['date_to']);

        $reviewItems = $q->orderByDesc('opname_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/StockOpnames/Review', [
            'reviewItems' => $reviewItems,
            'filters' => $filters,
            'options' => [
                'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function approve(StockOpname $stockOpname)
    {
        return DB::transaction(function () use ($stockOpname) {
            /** @var \App\Models\StockOpname $opn */
            $opn = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opn->validation === 'approved') {
                activity_log(
                    'stock_opnames',
                    'approve',
                    "Approve skipped (already approved): {$opn->code} (ID: {$opn->id})"
                );

                return back()->with('success', 'Sudah di-approve.');
            }

            /** @var \App\Models\Item $item */
            $item = Item::lockForUpdate()->findOrFail((int)$opn->item_id);

            $minRequired = (int)$item->stock_borrowed + (int)$item->stock_damaged;
            if ((int)$opn->physical_stock < $minRequired) {
                return back()->withErrors([
                    'approve' => "Tidak bisa approve: stok fisik ({$opn->physical_stock}) < borrowed+damaged ({$minRequired}).",
                ]);
            }

            $beforeTotal = (int) $item->stock_total;
            $beforeAvail = (int) $item->stock_available;

            $item->stock_total = (int)$opn->physical_stock;
            $item->stock_available = (int)$item->stock_total - (int)$item->stock_borrowed - (int)$item->stock_damaged;
            if ((int)$item->stock_available < 0) $item->stock_available = 0;
            $item->save();

            $opn->validation = 'approved';
            $opn->save();

            activity_log(
                'stock_opnames',
                'approve',
                "Approve opname: {$opn->code} (ID: {$opn->id})"
                    . " | item={$item->code} - {$item->name} (ID: {$item->id})"
                    . " | physical={$opn->physical_stock} | diff={$opn->difference}"
                    . " | stock_total: {$beforeTotal}→{$item->stock_total}"
                    . " | stock_available: {$beforeAvail}→{$item->stock_available}"
            );

            return back()->with('success', 'Opname discrepancy di-approve & stok sistem disesuaikan.');
        });
    }

    public function exportCsv(Request $request)
    {
        $loc = $request->query('location_id', '');
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        activity_log(
            'stock_opnames',
            'export',
            "Export stock opname (csv) | location_id=" . ($loc !== '' ? $loc : '-')
                . " | date_from=" . ($dateFrom !== '' ? $dateFrom : '-')
                . " | date_to=" . ($dateTo !== '' ? $dateTo : '-')
        );

        $q = StockOpname::query()
            ->with(['item:id,code,name,location_id', 'item.location:id,name'])
            ->orderByDesc('opname_date')->orderByDesc('id');

        if ($loc !== '') {
            $locId = (int)$loc;
            $q->whereHas('item', fn(Builder $iq) => $iq->where('location_id', $locId));
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
