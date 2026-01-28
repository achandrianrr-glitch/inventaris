<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandStoreRequest;
use App\Http\Requests\Admin\BrandUpdateRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $search  = trim((string) $request->query('search', ''));
        $status  = (string) $request->query('status', 'all');
        $trashed = (string) $request->query('trashed', 'without'); // without | with | only

        $query = Brand::query()
            ->select(['id', 'name', 'status', 'created_at', 'updated_at', 'deleted_at'])
            ->withCount('items');

        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $brands = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Brands/Index', [
            'brands' => $brands,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'trashed' => $trashed,
            ],
        ]);
    }

    public function store(BrandStoreRequest $request)
    {
        Brand::create($request->validated());

        return back()->with('success', 'Merek berhasil ditambahkan.');
    }

    public function update(BrandUpdateRequest $request, Brand $brand)
    {
        $brand->update($request->validated());

        return back()->with('success', 'Merek berhasil diperbarui.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return back()->with('success', 'Merek berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, int $id)
    {
        $brand = Brand::withTrashed()->findOrFail($id);
        $brand->restore();

        return back()->with('success', 'Merek berhasil dipulihkan.');
    }
}
