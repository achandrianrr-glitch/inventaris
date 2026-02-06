<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransactionInStoreRequest;
use App\Models\Item;
use App\Models\Transaction;
use App\Support\ItemCode;
use App\Support\StockAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransactionInController extends Controller
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
            ->where('type', 'in')
            ->with([
                'item:id,code,name',
                'admin:id,name',
            ])
            ->select([
                'id',
                'code',
                'type',
                'item_id',
                'qty',
                'from_location',
                'transaction_date',
                'admin_id',
                'notes',
                'status',
                'created_at',
            ]);

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $normalizedItemCode = ItemCode::normalize3DigitDash4($s); // ✅ LAB0001 -> LAB-0001

            $q->where(function (Builder $x) use ($s, $normalizedItemCode) {
                $x->where('code', 'like', "%{$s}%")

                    // item: nama / kode (smart normalize)
                    ->orWhereHas('item', function (Builder $iq) use ($s, $normalizedItemCode) {
                        $iq->where(function (Builder $z) use ($s, $normalizedItemCode) {
                            if ($normalizedItemCode) {
                                $z->orWhere('code', $normalizedItemCode);
                            }

                            $z->orWhere('code', 'like', "%{$s}%")
                                ->orWhere('name', 'like', "%{$s}%");
                        });
                    })

                    // opsional: cari dari sumber/dari lokasi juga
                    ->orWhere('from_location', 'like', "%{$s}%");
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

        return Inertia::render('Admin/Transactions/In/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'options' => [
                'items' => Item::query()
                    ->orderBy('name')
                    ->get(['id', 'code', 'name', 'status']),
            ],
        ]);
    }

    private function generateCode(): string
    {
        // contoh: IN260129-0001
        $prefix = 'IN' . date('ymd') . '-';

        $last = Transaction::query()
            ->where('type', 'in')
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

    public function store(TransactionInStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $adminId = (int) Auth::id();

        return DB::transaction(function () use ($data, $adminId) {
            /** @var \App\Models\Item $item */
            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['item_id']);

            $qty = (int) $data['qty'];

            // update stok (barang masuk)
            $item->stock_total = (int) $item->stock_total + $qty;
            $item->stock_available = (int) $item->stock_available + $qty;
            $item->save();

            StockAlert::lowStock($item, $adminId, 5);

            $trx = Transaction::query()->create([
                'code' => $this->generateCode(),
                'type' => 'in',
                'item_id' => $item->id,
                'qty' => $qty,
                'from_location' => $data['from_location'] ?? null,
                'to_location' => null,
                'transaction_date' => $data['transaction_date'],
                'admin_id' => $adminId,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ]);

            // LOG AKTIVITAS (biar konsisten dengan modul lain)
            activity_log(
                'transactions_in',
                'create',
                "Barang masuk: {$trx->code} (ID: {$trx->id}) | item={$item->code} - {$item->name} (ID: {$item->id})"
                    . " | qty={$qty} | from=" . ($trx->from_location ?? '-')
                    . " | date={$trx->transaction_date}"
                    . " | stock_total={$item->stock_total} | stock_available={$item->stock_available}"
            );

            return back()->with('success', 'Transaksi barang masuk berhasil disimpan & stok bertambah.');
        });
    }
}
