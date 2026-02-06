<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BorrowingStoreRequest;
use App\Models\Borrower;
use App\Models\Borrowing;
use App\Models\Item;
use App\Support\ItemCode;
use App\Support\StockAlert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BorrowingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', 'all'), // all | borrowed | late | returned | damaged | lost
            'type'   => (string) $request->query('type', 'all'),   // all | lesson | daily
        ];

        $q = Borrowing::query()
            ->with([
                'borrower:id,name,type,class,major,status',
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
                'borrow_time',
                'return_due',
                'return_date',
                'status',
                'notes',
                'admin_id',
                'created_at',
            ]);

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $normalizedItemCode = ItemCode::normalize3DigitDash4($s); // ✅ LAB0001 -> LAB-0001

            $q->where(function (Builder $x) use ($s, $normalizedItemCode) {
                // 1) kode peminjaman
                $x->where('code', 'like', "%{$s}%")

                    // 2) borrower: nama / id_number
                    ->orWhereHas('borrower', function (Builder $bq) use ($s) {
                        $bq->where('name', 'like', "%{$s}%")
                            ->orWhere('id_number', 'like', "%{$s}%");
                    })

                    // 3) item: nama / kode (smart normalize)
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

        if ($filters['type'] !== 'all' && in_array($filters['type'], ['lesson', 'daily'], true)) {
            $q->where('borrow_type', $filters['type']);
        }

        if ($filters['status'] !== 'all' && in_array($filters['status'], ['borrowed', 'late', 'returned', 'damaged', 'lost'], true)) {
            $q->where('status', $filters['status']);
        }

        $borrowings = $q->orderByDesc('created_at')->paginate(10)->withQueryString();

        // indikator terlambat (tanpa cron): flag overdue saat status masih borrowed & due lewat
        $borrowings->through(function ($row) {
            $isOverdue = ($row->status === 'borrowed') && $row->return_due && now()->gt($row->return_due);
            $row->is_overdue = $isOverdue;
            $row->display_status = $isOverdue ? 'late' : $row->status;
            return $row;
        });

        // KPI ringan
        $activeCount = Borrowing::whereIn('status', ['borrowed', 'late'])->count();
        $overdueCount = Borrowing::where('status', 'borrowed')->where('return_due', '<', now())->count();

        return Inertia::render('Admin/Borrowings/Index', [
            'borrowings' => $borrowings,
            'filters' => $filters,
            'kpi' => [
                'active' => $activeCount,
                'overdue' => $overdueCount,
            ],
            'options' => [
                'borrowers' => Borrower::query()
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get(['id', 'name', 'type', 'class', 'major', 'status']),
                'items' => Item::query()
                    ->orderBy('name')
                    ->with(['brand:id,name'])
                    ->get(['id', 'code', 'name', 'brand_id', 'stock_available', 'status']),
            ],
        ]);
    }

    private function generateCode(): string
    {
        // contoh: BRW260129-0001
        $prefix = 'BRW' . date('ymd') . '-';

        $last = Borrowing::query()
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

    public function store(BorrowingStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $adminId = (int) Auth::id();

        return DB::transaction(function () use ($data, $adminId) {
            $borrowerId = (int) $data['borrower_id'];
            $itemId = (int) $data['item_id'];
            $qty = (int) $data['qty'];

            /** @var \App\Models\Item $item */
            $item = Item::query()->lockForUpdate()->findOrFail($itemId);

            $borrower = Borrower::query()->findOrFail($borrowerId);
            if ($borrower->status !== 'active') {
                return back()->withErrors(['borrower_id' => 'Peminjam sedang diblokir.']);
            }

            $hasActive = Borrowing::query()
                ->where('borrower_id', $borrowerId)
                ->whereIn('status', ['borrowed', 'late'])
                ->exists();

            if ($hasActive) {
                return back()->withErrors(['borrower_id' => 'Masih ada pinjaman aktif untuk peminjam ini.']);
            }

            if ((int) $item->stock_available < $qty) {
                return back()->withErrors(['qty' => 'Stok tersedia tidak cukup.']);
            }

            $borrowDate = Carbon::parse($data['borrow_date'])->startOfDay();
            $borrowTime = !empty($data['borrow_time']) ? $data['borrow_time'] : null;

            if (($data['borrow_type'] ?? '') === 'lesson') {
                $returnDue = Carbon::parse($data['borrow_date'])->endOfDay();
            } else {
                $returnDue = Carbon::parse($data['return_due_date'])->endOfDay();

                if ($returnDue->lt($borrowDate)) {
                    return back()->withErrors(['return_due_date' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.']);
                }
            }

            $item->stock_available = (int) $item->stock_available - $qty;
            $item->stock_borrowed  = (int) $item->stock_borrowed + $qty;
            $item->save();

            StockAlert::lowStock($item, $adminId, 5);

            $borrowing = Borrowing::query()->create([
                'code' => $this->generateCode(),
                'borrower_id' => $borrowerId,
                'item_id' => $itemId,
                'qty' => $qty,
                'borrow_type' => $data['borrow_type'],

                'lesson_hour' => $data['borrow_type'] === 'lesson' ? ($data['lesson_hour'] ?? null) : null,
                'subject'     => $data['borrow_type'] === 'lesson' ? ($data['subject'] ?? null) : null,
                'teacher'     => $data['borrow_type'] === 'lesson' ? ($data['teacher'] ?? null) : null,

                'borrow_date' => $data['borrow_date'],
                'borrow_time' => $borrowTime,
                'return_due'  => $returnDue,
                'return_date' => null,
                'return_condition' => null,

                'status'   => 'borrowed',
                'admin_id' => $adminId,
                'notes'    => $data['notes'] ?? null,
            ]);

            activity_log(
                'borrowings',
                'create',
                "Buat peminjaman: {$borrowing->code} (ID: {$borrowing->id}) | peminjam={$borrower->name} (ID: {$borrower->id})"
                    . " | barang={$item->code} - {$item->name} (ID: {$item->id}) | qty={$qty} | type={$borrowing->borrow_type}"
                    . " | due={$returnDue->format('Y-m-d H:i:s')}"
                    . " | stok_available={$item->stock_available} | stok_borrowed={$item->stock_borrowed}"
            );

            return back()->with('success', 'Peminjaman berhasil disimpan. Stok tersedia berkurang & stok dipinjam bertambah.');
        });
    }
}
