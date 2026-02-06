<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DamageStoreRequest;
use App\Http\Requests\Admin\DamageUpdateRequest;
use App\Models\Borrowing;
use App\Models\Damage;
use App\Models\Item;
use App\Models\Location;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DamageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search'      => trim((string) $request->query('search', '')),
            'status'      => (string) $request->query('status', 'all'),       // all|pending|in_progress|completed
            'level'       => (string) $request->query('level', 'all'),         // all|minor|moderate|heavy
            'location_id' => (string) $request->query('location_id', ''),      // optional
        ];

        $q = Damage::query()
            ->with([
                'item:id,code,name,location_id',
                'item.location:id,name',
                'borrowing:id,code,qty,borrower_id',
                'borrowing.borrower:id,name',
                'admin:id,name',
            ])
            ->select([
                'id',
                'code',
                'item_id',
                'borrowing_id',
                'damage_level',
                'description',
                'reported_date',
                'status',
                'solution',
                'completion_date',
                'admin_id',
                'created_at',
            ]);

        if ($filters['search'] !== '') {
            $s = $filters['search'];

            $q->where(function (Builder $x) use ($s) {
                $x->where('code', 'like', "%{$s}%")
                    ->orWhereHas('item', function ($iq) use ($s) {
                        $iq->where('name', 'like', "%{$s}%")
                            ->orWhere('code', 'like', "%{$s}%");
                    })
                    ->orWhereHas('borrowing', function ($bq) use ($s) {
                        $bq->where('code', 'like', "%{$s}%");
                    });
            });
        }

        if ($filters['status'] !== 'all' && in_array($filters['status'], ['pending', 'in_progress', 'completed'], true)) {
            $q->where('status', $filters['status']);
        }

        if ($filters['level'] !== 'all' && in_array($filters['level'], ['minor', 'moderate', 'heavy'], true)) {
            $q->where('damage_level', $filters['level']);
        }

        if ($filters['location_id'] !== '') {
            $locId = (int) $filters['location_id'];
            $q->whereHas('item', fn($iq) => $iq->where('location_id', $locId));
        }

        $damages = $q->orderByDesc('reported_date')->orderByDesc('id')->paginate(10)->withQueryString();

        // options
        $items = Item::query()
            ->orderBy('name')
            ->with(['location:id,name'])
            ->get(['id', 'code', 'name', 'location_id', 'stock_damaged', 'status']);

        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        // link peminjaman (opsional) — limit biar dropdown tidak berat
        $borrowings = Borrowing::query()
            ->orderByDesc('id')
            ->with(['borrower:id,name'])
            ->limit(80)
            ->get(['id', 'code', 'qty', 'borrower_id', 'return_condition', 'status']);

        return Inertia::render('Admin/Damages/Index', [
            'damages' => $damages,
            'filters' => $filters,
            'options' => [
                'items' => $items,
                'locations' => $locations,
                'borrowings' => $borrowings,
            ],
        ]);
    }

    private function generateCode(): string
    {
        $prefix = 'DMG' . date('ymd') . '-';

        $last = Damage::query()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $num = (int) end($parts);
            $next = $num + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function store(DamageStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // ✅ ganti auth()->id() biar IDE tidak merah
        $adminId = (int) Auth::id();

        return DB::transaction(function () use ($data, $adminId) {
            /** @var \App\Models\Item $item */
            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['item_id']);

            $borrowingId = $data['borrowing_id'] ?? null;
            $qtyImpact = 1;

            $borrowingCode = null;

            if ($borrowingId) {
                $borrowing = Borrowing::query()->findOrFail((int) $borrowingId);
                $borrowingCode = $borrowing->code;

                // anti double-log dari Tahap 13 (kalau sudah dibuat otomatis saat pengembalian rusak)
                if ($borrowing->return_condition === 'damaged' || $borrowing->status === 'damaged') {
                    return back()->withErrors([
                        'borrowing_id' => 'Peminjaman ini sudah tercatat sebagai rusak dari pengembalian.',
                    ]);
                }

                $exists = Damage::query()
                    ->where('borrowing_id', $borrowing->id)
                    ->exists();

                if ($exists) {
                    return back()->withErrors([
                        'borrowing_id' => 'Kerusakan untuk peminjaman ini sudah pernah dibuat.',
                    ]);
                }

                $qtyImpact = max(1, (int) $borrowing->qty);
            }

            $beforeDamaged = (int) $item->stock_damaged;

            // laporan kerusakan manual: tambahkan stok rusak
            $item->stock_damaged = (int) $item->stock_damaged + $qtyImpact;
            $item->save();

            $damage = Damage::query()->create([
                'code' => $this->generateCode(),
                'item_id' => $item->id,
                'borrowing_id' => $borrowingId,
                'damage_level' => $data['damage_level'],
                'description' => $data['description'],
                'reported_date' => $data['reported_date'],
                'status' => 'pending',
                'solution' => null,
                'completion_date' => null,
                'admin_id' => $adminId,
            ]);

            // notifikasi kerusakan (opsional)
            Notification::query()->create([
                'type' => 'damage',
                'title' => 'Kerusakan baru',
                'message' => "Barang {$item->code} — {$item->name} dilaporkan rusak.",
                'reference_id' => $damage->id,
                'reference_type' => 'damage',
                'is_read' => false,
                'admin_id' => $adminId,
            ]);

            // LOG AKTIVITAS (sukses)
            $desc = "Buat laporan kerusakan: {$damage->code} (ID: {$damage->id})"
                . " | barang={$item->code} - {$item->name} (ID: {$item->id})"
                . " | level={$damage->damage_level}"
                . " | qty_impact={$qtyImpact}"
                . " | stok_damaged: {$beforeDamaged}→{$item->stock_damaged}"
                . " | reported_date={$damage->reported_date}";

            if ($borrowingId) {
                $desc .= " | ref_peminjaman={$borrowingCode} (ID: {$borrowingId})";
            }

            activity_log('damages', 'create', $desc);

            return back()->with('success', 'Laporan kerusakan berhasil dibuat.');
        });
    }

    public function update(DamageUpdateRequest $request, Damage $damage): RedirectResponse
    {
        $data = $request->validated();

        $before = [
            'status' => $damage->status,
            'solution' => $damage->solution,
            'completion_date' => $damage->completion_date,
        ];

        return DB::transaction(function () use ($data, $damage, $before) {
            $payload = [
                'status' => $data['status'],
                'solution' => $data['solution'] ?? null,
                'completion_date' => $data['completion_date'] ?? null,
            ];

            // kalau status bukan completed, kosongkan completion_date
            if ($data['status'] !== 'completed') {
                $payload['completion_date'] = null;
            }

            $damage->update($payload);

            // LOG AKTIVITAS (sukses update)
            $after = [
                'status' => $damage->status,
                'solution' => $damage->solution,
                'completion_date' => $damage->completion_date,
            ];

            $changes = [];
            foreach ($before as $k => $v) {
                $av = $after[$k] ?? null;
                if ($av !== $v) {
                    $changes[] = "{$k}: " . ($v ?? '-') . " → " . ($av ?? '-');
                }
            }

            $desc = "Update kerusakan: {$damage->code} (ID: {$damage->id})";
            if (!empty($changes)) {
                $desc .= " | " . implode(' | ', $changes);
            }

            activity_log('damages', 'update', $desc);

            return back()->with('success', 'Kerusakan berhasil diperbarui.');
        });
    }
}
