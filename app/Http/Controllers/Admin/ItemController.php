<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ItemsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemStoreRequest;
use App\Http\Requests\Admin\ItemUpdateRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Support\ItemCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search'      => trim((string) $request->query('search', '')),
            'category_id' => $request->query('category_id'),
            'brand_id'    => $request->query('brand_id'),
            'location_id' => $request->query('location_id'),
            'status'      => (string) $request->query('status', 'all'),
            'condition'   => (string) $request->query('condition', 'all'),
            'trashed'     => (string) $request->query('trashed', 'without'), // without | with | only
        ];

        $q = Item::query()
            ->select([
                'id',
                'code',
                'name',
                'category_id',
                'brand_id',
                'location_id',
                'specification',
                'purchase_year',
                'purchase_price',
                'stock_total',
                'stock_available',
                'stock_borrowed',
                'stock_damaged',
                'condition',
                'status',
                'updated_at',
                'deleted_at',
            ])
            ->with([
                'category:id,name',
                'brand:id,name',
                'location:id,name',
            ]);

        if ($filters['trashed'] === 'with') {
            $q->withTrashed();
        }
        if ($filters['trashed'] === 'only') {
            $q->onlyTrashed();
        }

        // ✅ SEARCH + SMART MATCH kode unik XXX-0000
        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $normalized = ItemCode::normalize3DigitDash4($s);

            $q->where(function (Builder $x) use ($s, $normalized) {
                // search umum
                $x->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('specification', 'like', "%{$s}%");

                // exact-match kode hasil normalisasi (misal "lab1" -> "LAB-0001")
                if ($normalized) {
                    $x->orWhere('code', $normalized);
                }
            });
        }

        foreach (['category_id', 'brand_id', 'location_id'] as $k) {
            if (!empty($filters[$k])) {
                $q->where($k, (int) $filters[$k]);
            }
        }

        foreach (['status', 'condition'] as $k) {
            if (!empty($filters[$k]) && $filters[$k] !== 'all') {
                $q->where($k, $filters[$k]);
            }
        }

        $items = $q->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Items/Index', [
            'items'   => $items,
            'filters' => $filters,
            'options' => [
                'categories' => Category::orderBy('name')->get(['id', 'name', 'status']),
                'brands'     => Brand::orderBy('name')->get(['id', 'name', 'status']),
                'locations'  => Location::orderBy('name')->get(['id', 'name', 'status']),
            ],
        ]);
    }

    /**
     * Prefix kode:
     * - Ambil dari settings.code_format (jika tabel & kolom ada)
     * - Harus 3 karakter (A-Z / 0-9)
     * - Fallback: LAB
     */
    private function getCodePrefix(): string
    {
        $raw = null;

        // Aman kalau tabel/kolom settings belum ada
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'code_format')) {
            $raw = DB::table('settings')->orderBy('id')->value('code_format');
        }

        $raw = strtoupper((string) ($raw ?? ''));
        $raw = preg_replace('/[^A-Z0-9]/', '', $raw) ?: '';

        $prefix = substr($raw, 0, 3);
        if (strlen($prefix) !== 3) {
            $prefix = 'LAB';
        }

        return $prefix;
    }

    /**
     * Generate code format: XXX-0000 (increment per prefix)
     * Contoh: LAB-0001, LAB-0002
     * Menghitung termasuk soft deleted agar tidak bentrok.
     *
     * Lebih aman: pakai transaction + lock (untuk mengurangi risiko dobel kode).
     */
    private function generateCode(): string
    {
        $prefix = $this->getCodePrefix();
        $like = $prefix . '-%';

        return DB::transaction(function () use ($prefix, $like) {
            $last = Item::withTrashed()
                ->where('code', 'like', $like)
                ->orderByDesc('code')
                ->lockForUpdate()
                ->value('code');

            $next = 1;

            if ($last && preg_match('/^' . preg_quote($prefix, '/') . '\-(\d{4})$/', $last, $m)) {
                $next = ((int) $m[1]) + 1;
            }

            $code = sprintf('%s-%04d', $prefix, $next);

            while (Item::withTrashed()->where('code', $code)->exists()) {
                $next++;
                $code = sprintf('%s-%04d', $prefix, $next);
            }

            return $code;
        }, 3);
    }

    public function store(ItemStoreRequest $request)
    {
        $data = $request->validated();

        // ✅ code auto format XXX-0000
        $data['code'] = $this->generateCode();

        // stok awal
        $data['stock_borrowed']  = 0;
        $data['stock_damaged']   = 0;
        $data['stock_available'] = (int) $data['stock_total'];

        $item = Item::create($data);

        activity_log(
            'items',
            'create',
            "Tambah barang: {$item->code} - {$item->name} (ID: {$item->id}) | total={$item->stock_total} | available={$item->stock_available}"
        );

        return back()->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(ItemUpdateRequest $request, Item $item)
    {
        $before = [
            'name'            => $item->name,
            'category_id'     => $item->category_id,
            'brand_id'        => $item->brand_id,
            'location_id'     => $item->location_id,
            'stock_total'     => (int) $item->stock_total,
            'stock_available' => (int) $item->stock_available,
            'condition'       => $item->condition,
            'status'          => $item->status,
        ];

        $data = $request->validated();

        // jaga konsistensi stok (available = total - borrowed - damaged)
        $borrowed = (int) $item->stock_borrowed;
        $damaged  = (int) $item->stock_damaged;
        $total    = (int) $data['stock_total'];

        $data['stock_available'] = max(0, $total - $borrowed - $damaged);

        $item->update($data);

        $after = [
            'name'            => $item->name,
            'category_id'     => $item->category_id,
            'brand_id'        => $item->brand_id,
            'location_id'     => $item->location_id,
            'stock_total'     => (int) $item->stock_total,
            'stock_available' => (int) $item->stock_available,
            'condition'       => $item->condition,
            'status'          => $item->status,
        ];

        $changes = [];
        foreach ($before as $k => $v) {
            if (($after[$k] ?? null) !== $v) {
                $changes[] = "{$k}: " . ($v ?? '-') . " → " . ($after[$k] ?? '-');
            }
        }

        $desc = "Update barang: {$item->code} - {$item->name} (ID: {$item->id})";
        if (!empty($changes)) {
            $desc .= " | " . implode(' | ', $changes);
        }

        activity_log('items', 'update', $desc);

        return back()->with('success', 'Barang berhasil diperbarui.');
    }

    public function show(Item $item): Response
    {
        $item->load(['category:id,name', 'brand:id,name', 'location:id,name']);

        return Inertia::render('Admin/Items/Show', [
            'item' => $item,
        ]);
    }

    public function destroy(Item $item)
    {
        $code = $item->code;
        $name = $item->name;
        $id   = $item->id;

        $item->delete();

        activity_log('items', 'delete', "Soft delete barang: {$code} - {$name} (ID: {$id})");

        return back()->with('success', 'Barang berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, int $id)
    {
        $item = Item::withTrashed()->findOrFail($id);
        $item->restore();

        activity_log('items', 'restore', "Restore barang: {$item->code} - {$item->name} (ID: {$item->id})");

        return back()->with('success', 'Barang berhasil dipulihkan.');
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->only([
            'search',
            'category_id',
            'brand_id',
            'location_id',
            'status',
            'condition',
            'trashed',
        ]);

        $desc = "Export barang (excel)"
            . " | search=" . (!empty($filters['search']) ? $filters['search'] : '-')
            . " | category_id=" . (!empty($filters['category_id']) ? $filters['category_id'] : '-')
            . " | brand_id=" . (!empty($filters['brand_id']) ? $filters['brand_id'] : '-')
            . " | location_id=" . (!empty($filters['location_id']) ? $filters['location_id'] : '-')
            . " | status=" . (!empty($filters['status']) ? $filters['status'] : '-')
            . " | condition=" . (!empty($filters['condition']) ? $filters['condition'] : '-')
            . " | trashed=" . (!empty($filters['trashed']) ? $filters['trashed'] : '-');

        activity_log('items', 'export', $desc);

        return Excel::download(new ItemsExport($filters), 'barang.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $filters = [
            'search'      => trim((string) $request->query('search', '')),
            'category_id' => $request->query('category_id'),
            'brand_id'    => $request->query('brand_id'),
            'location_id' => $request->query('location_id'),
            'status'      => (string) $request->query('status', 'all'),
            'condition'   => (string) $request->query('condition', 'all'),
            'trashed'     => (string) $request->query('trashed', 'without'),
        ];

        $desc = "Export barang (pdf)"
            . " | search=" . ($filters['search'] !== '' ? $filters['search'] : '-')
            . " | category_id=" . (!empty($filters['category_id']) ? $filters['category_id'] : '-')
            . " | brand_id=" . (!empty($filters['brand_id']) ? $filters['brand_id'] : '-')
            . " | location_id=" . (!empty($filters['location_id']) ? $filters['location_id'] : '-')
            . " | status=" . (!empty($filters['status']) ? $filters['status'] : '-')
            . " | condition=" . (!empty($filters['condition']) ? $filters['condition'] : '-')
            . " | trashed=" . (!empty($filters['trashed']) ? $filters['trashed'] : '-');

        activity_log('items', 'export', $desc);

        $q = Item::query()->with(['category:id,name', 'brand:id,name', 'location:id,name']);

        if ($filters['trashed'] === 'with') {
            $q->withTrashed();
        }
        if ($filters['trashed'] === 'only') {
            $q->onlyTrashed();
        }

        // ✅ SEARCH + SMART MATCH kode unik XXX-0000 (PDF)
        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $normalized = ItemCode::normalize3DigitDash4($s);

            $q->where(function (Builder $x) use ($s, $normalized) {
                $x->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('specification', 'like', "%{$s}%");

                if ($normalized) {
                    $x->orWhere('code', $normalized);
                }
            });
        }

        foreach (['category_id', 'brand_id', 'location_id'] as $k) {
            if (!empty($filters[$k])) {
                $q->where($k, (int) $filters[$k]);
            }
        }

        foreach (['status', 'condition'] as $k) {
            if (!empty($filters[$k]) && $filters[$k] !== 'all') {
                $q->where($k, $filters[$k]);
            }
        }

        $items = $q->orderByDesc('updated_at')->limit(2000)->get();

        $pdf = Pdf::loadView('exports.items_pdf', compact('items'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('barang.pdf');
    }
}
