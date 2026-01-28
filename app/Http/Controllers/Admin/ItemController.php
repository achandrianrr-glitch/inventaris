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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string)$request->query('search', '')),
            'category_id' => $request->query('category_id'),
            'brand_id' => $request->query('brand_id'),
            'location_id' => $request->query('location_id'),
            'status' => (string)$request->query('status', 'all'),
            'condition' => (string)$request->query('condition', 'all'),
            'trashed' => (string)$request->query('trashed', 'without'), // without | with | only
        ];

        $q = Item::query()
            ->select([
                'id',
                'code',
                'name',
                'category_id',
                'brand_id',
                'location_id',
                'purchase_year',
                'purchase_price',
                'stock_total',
                'stock_available',
                'stock_borrowed',
                'stock_damaged',
                'condition',
                'status',
                'updated_at',
                'deleted_at'
            ])
            ->with([
                'category:id,name',
                'brand:id,name',
                'location:id,name',
            ]);

        if ($filters['trashed'] === 'with') $q->withTrashed();
        if ($filters['trashed'] === 'only') $q->onlyTrashed();

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $q->where(function (Builder $x) use ($s) {
                $x->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('specification', 'like', "%{$s}%");
            });
        }

        foreach (['category_id', 'brand_id', 'location_id'] as $k) {
            if (!empty($filters[$k])) $q->where($k, (int)$filters[$k]);
        }

        foreach (['status', 'condition'] as $k) {
            if (!empty($filters[$k]) && $filters[$k] !== 'all') $q->where($k, $filters[$k]);
        }

        $items = $q->orderByDesc('updated_at')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Items/Index', [
            'items' => $items,
            'filters' => $filters,
            'options' => [
                'categories' => Category::orderBy('name')->get(['id', 'name', 'status']),
                'brands' => Brand::orderBy('name')->get(['id', 'name', 'status']),
                'locations' => Location::orderBy('name')->get(['id', 'name', 'status']),
            ],
        ]);
    }

    private function generateCode(): string
    {
        $prefix = 'ITM' . date('ymd') . '-';
        $last = Item::withTrashed()
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

    public function store(ItemStoreRequest $request)
    {
        $data = $request->validated();

        $data['code'] = $this->generateCode();

        // stok awal
        $data['stock_borrowed'] = 0;
        $data['stock_damaged'] = 0;
        $data['stock_available'] = (int)$data['stock_total'];

        Item::create($data);

        return back()->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(ItemUpdateRequest $request, Item $item)
    {
        $data = $request->validated();

        // jaga konsistensi stok (available mengikuti total - borrowed - damaged)
        $borrowed = (int)$item->stock_borrowed;
        $damaged = (int)$item->stock_damaged;
        $total = (int)$data['stock_total'];
        $data['stock_available'] = max(0, $total - $borrowed - $damaged);

        $item->update($data);

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
        $item->delete();
        return back()->with('success', 'Barang berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, int $id)
    {
        $item = Item::withTrashed()->findOrFail($id);
        $item->restore();
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
            'trashed'
        ]);

        return Excel::download(new ItemsExport($filters), 'barang.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $filters = [
            'search' => trim((string)$request->query('search', '')),
            'category_id' => $request->query('category_id'),
            'brand_id' => $request->query('brand_id'),
            'location_id' => $request->query('location_id'),
            'status' => (string)$request->query('status', 'all'),
            'condition' => (string)$request->query('condition', 'all'),
            'trashed' => (string)$request->query('trashed', 'without'),
        ];

        $q = Item::query()->with(['category:id,name', 'brand:id,name', 'location:id,name']);

        if ($filters['trashed'] === 'with') $q->withTrashed();
        if ($filters['trashed'] === 'only') $q->onlyTrashed();

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $q->where(function (Builder $x) use ($s) {
                $x->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('specification', 'like', "%{$s}%");
            });
        }

        foreach (['category_id', 'brand_id', 'location_id'] as $k) {
            if (!empty($filters[$k])) $q->where($k, (int)$filters[$k]);
        }

        foreach (['status', 'condition'] as $k) {
            if (!empty($filters[$k]) && $filters[$k] !== 'all') $q->where($k, $filters[$k]);
        }

        $items = $q->orderByDesc('updated_at')->limit(2000)->get();

        $pdf = Pdf::loadView('exports.items_pdf', compact('items'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('barang.pdf');
    }
}
