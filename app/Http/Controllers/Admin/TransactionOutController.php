<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransactionOutStoreRequest;
use App\Models\Item;
use App\Models\Transaction;
use App\Support\StockAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransactionOutController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search'    => trim((string) $request->query('search', '')),
            'item_id'   => (string) $request->query('item_id', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to'   => (string) $request->query('date_to', ''),
        ];

        $q = Transaction::query()
            ->where('type', 'out')
            ->with([
                // kalau mau tampilkan item trashed juga:
                // ubah relasi item() di Transaction jadi ->withTrashed()
                'item:id,code,name',
                'admin:id,name',
            ])
            ->select([
                'id',
                'code',
                'type',
                'item_id',
                'qty',
                'to_location',
                'transaction_date',
                'admin_id',
                'notes',
                'status',
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
                    ->orWhere('to_location', 'like', "%{$s}%");
            });
        }

        if ($filters['item_id'] !== '') {
            $q->where('item_id', (int) $filters['item_id']);
        }

        if ($filters['date_from'] !== '') {
            $q->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $q->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        $transactions = $q->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Transactions/Out/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'options' => [
                // ✅ hanya item layak transaksi (aktif & bukan trashed)
                'items' => Item::query()
                    ->whereNull('deleted_at')
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get(['id', 'code', 'name', 'stock_available', 'status']),
            ],
        ]);
    }

    private function generateCode(): string
    {
        $prefix = 'OUT' . date('ymd') . '-';

        $last = Transaction::query()
            ->where('type', 'out')
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

    public function store(TransactionOutStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // ✅ ganti auth()->id() supaya IDE tidak merah
        $adminId = (int) Auth::id();

        return DB::transaction(function () use ($data, $adminId) {
            /** @var \App\Models\Item $item */
            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['item_id']);

            // ✅ blok kalau item sudah soft delete
            if (!is_null($item->deleted_at)) {
                return back()->withErrors([
                    'item_id' => 'Barang ini sudah dihapus (trash). Silakan pilih barang lain.',
                ]);
            }

            // ✅ blok kalau status item tidak active
            if (($item->status ?? 'active') !== 'active') {
                return back()->withErrors([
                    'item_id' => 'Barang ini tidak aktif / sedang service. Tidak bisa transaksi barang keluar.',
                ]);
            }

            $qty = (int) $data['qty'];

            if ((int) $item->stock_available < $qty) {
                return back()->withErrors([
                    'qty' => 'Stok tersedia tidak cukup.',
                ]);
            }

            // update stok (barang keluar)
            $item->stock_available = (int) $item->stock_available - $qty;
            $item->save();

            StockAlert::lowStock($item, $adminId, 5);

            Transaction::query()->create([
                'code' => $this->generateCode(),
                'type' => 'out',
                'item_id' => $item->id,
                'qty' => $qty,
                'from_location' => null,
                'to_location' => $data['to_location'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'admin_id' => $adminId,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ]);

            return back()->with('success', 'Transaksi barang keluar berhasil.');
        });
    }
}
