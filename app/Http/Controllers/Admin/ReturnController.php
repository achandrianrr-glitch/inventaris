<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnStoreRequest;
use App\Models\Borrower;
use App\Models\Borrowing;
use App\Models\Damage;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search'    => trim((string) $request->query('search', '')),
            'status'    => (string) $request->query('status', 'all'), // returned | damaged | lost | all
            'date_from' => (string) $request->query('date_from', ''),
            'date_to'   => (string) $request->query('date_to', ''),
        ];

        $q = Borrowing::query()
            ->whereNotNull('return_date')
            ->with([
                'borrower:id,name,type,class,major',
                'item:id,code,name,brand_id',
                'item.brand:id,name',
                'admin:id,name',
            ])
            ->select([
                'id',
                'code',
                'borrower_id',
                'item_id',
                'qty',
                'borrow_type',
                'borrow_date',
                'return_due',
                'return_date',
                'return_condition',
                'status',
                'admin_id',
                'notes',
                'created_at',
            ]);

        if ($filters['search'] !== '') {
            $s = $filters['search'];

            $q->where(function (Builder $x) use ($s) {
                $x->where('code', 'like', "%{$s}%")
                    ->orWhereHas('borrower', function ($bq) use ($s) {
                        $bq->where('name', 'like', "%{$s}%");
                    })
                    ->orWhereHas('item', function ($iq) use ($s) {
                        $iq->where('name', 'like', "%{$s}%")
                            ->orWhere('code', 'like', "%{$s}%");
                    });
            });
        }

        if ($filters['status'] !== 'all' && in_array($filters['status'], ['returned', 'damaged', 'lost'], true)) {
            $q->where('status', $filters['status']);
        }

        if ($filters['date_from'] !== '') {
            $q->whereDate('return_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $q->whereDate('return_date', '<=', $filters['date_to']);
        }

        $returns = $q->orderByDesc('return_date')->paginate(10)->withQueryString();

        // opsi peminjam yg punya pinjaman aktif
        $borrowers = Borrower::query()
            ->whereHas('borrowings', function ($bq) {
                $bq->whereIn('status', ['borrowed', 'late'])
                    ->whereNull('return_date');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'class', 'major', 'status']);

        return Inertia::render('Admin/Returns/Index', [
            'returns' => $returns,
            'filters' => $filters,
            'options' => [
                'borrowers' => $borrowers,
            ],
        ]);
    }

    public function activeBorrowings(Request $request): JsonResponse
    {
        $borrowerId = (int) $request->query('borrower_id');

        $rows = Borrowing::query()
            ->where('borrower_id', $borrowerId)
            ->whereIn('status', ['borrowed', 'late'])
            ->whereNull('return_date')
            ->with([
                'item:id,code,name,brand_id,stock_available,stock_borrowed,stock_damaged',
                'item.brand:id,name',
            ])
            ->orderByDesc('created_at')
            ->get([
                'id',
                'code',
                'borrower_id',
                'item_id',
                'qty',
                'borrow_type',
                'borrow_date',
                'return_due',
                'status',
                'notes',
                'created_at',
            ]);

        return response()->json($rows);
    }

    private function generateDamageCode(): string
    {
        // contoh: DMG260129-0001
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

    public function store(ReturnStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // ✅ ganti auth()->id() supaya IDE tidak merah
        $adminId = (int) Auth::id();

        return DB::transaction(function () use ($data, $adminId) {
            $borrowerId  = (int) $data['borrower_id'];
            $borrowingId = (int) $data['borrowing_id'];
            $condition   = (string) $data['return_condition'];

            /** @var \App\Models\Borrowing $borrowing */
            $borrowing = Borrowing::query()
                ->lockForUpdate()
                ->findOrFail($borrowingId);

            // Validasi: borrowing milik borrower yg dipilih & masih aktif
            if ((int) $borrowing->borrower_id !== $borrowerId) {
                return back()->withErrors(['borrowing_id' => 'Data peminjaman tidak cocok dengan peminjam.']);
            }

            if (!in_array($borrowing->status, ['borrowed', 'late'], true) || $borrowing->return_date !== null) {
                return back()->withErrors(['borrowing_id' => 'Peminjaman ini sudah dikembalikan / tidak aktif.']);
            }

            /** @var \App\Models\Item $item */
            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail((int) $borrowing->item_id);

            $qty = (int) $borrowing->qty;

            $returnDate = !empty($data['return_date'])
                ? Carbon::parse($data['return_date'])
                : now();

            // hitung terlambat (untuk laporan sederhana)
            $isLate = $borrowing->return_due && $returnDate->gt($borrowing->return_due);

            // Update stok & status berdasarkan kondisi
            if ($condition === 'normal') {
                $item->stock_available = (int) $item->stock_available + $qty;
                $item->stock_borrowed  = max(0, (int) $item->stock_borrowed - $qty);
                $item->save();

                $borrowing->update([
                    'return_date' => $returnDate,
                    'return_condition' => 'normal',
                    'status' => 'returned',
                    'notes' => $data['notes'] ?? $borrowing->notes,
                ]);
            } elseif ($condition === 'damaged') {
                // barang tidak kembali ke available, masuk stock_damaged
                $item->stock_damaged  = (int) $item->stock_damaged + $qty;
                $item->stock_borrowed = max(0, (int) $item->stock_borrowed - $qty);
                $item->save();

                $borrowing->update([
                    'return_date' => $returnDate,
                    'return_condition' => 'damaged',
                    'status' => 'damaged',
                    'notes' => $data['notes'] ?? $borrowing->notes,
                ]);

                // tiket kerusakan otomatis (1 tiket per transaksi pengembalian rusak)
                Damage::query()->create([
                    'code' => $this->generateDamageCode(),
                    'item_id' => $item->id,
                    'borrowing_id' => $borrowing->id,
                    'damage_level' => 'moderate',
                    'description' => $data['notes'] ?? 'Barang dikembalikan dalam kondisi rusak.',
                    'reported_date' => $returnDate->toDateString(),
                    'status' => 'pending',
                    'solution' => null,
                    'completion_date' => null,
                    'admin_id' => $adminId,
                ]);
            } else { // lost
                // barang hilang: borrowed turun, available tidak naik, total tetap (sesuai blueprint kamu)
                $item->stock_borrowed = max(0, (int) $item->stock_borrowed - $qty);
                $item->save();

                $borrowing->update([
                    'return_date' => $returnDate,
                    'return_condition' => 'lost',
                    'status' => 'lost',
                    'notes' => $data['notes'] ?? $borrowing->notes,
                ]);
            }

            // (opsional) info late: tetap bisa dihitung dari return_due vs return_date pada laporan
            if ($isLate && $condition !== 'normal') {
                // intentionally no-op
            }

            return back()->with('success', 'Pengembalian berhasil diproses.');
        });
    }
}
