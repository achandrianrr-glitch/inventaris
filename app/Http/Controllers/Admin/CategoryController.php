<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CategoriesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryStoreRequest;
use App\Http\Requests\Admin\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search  = trim((string) $request->query('search', ''));
        $status  = (string) $request->query('status', 'all');
        $trashed = (string) $request->query('trashed', 'without'); // without | with | only

        $query = Category::query()->select([
            'id',
            'name',
            'description',
            'status',
            'created_at',
            'updated_at',
            'deleted_at'
        ]);

        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $categories = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'trashed' => $trashed,
            ],
        ]);
    }

    public function store(CategoryStoreRequest $request)
    {
        Category::create($request->validated());

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $category->update($request->validated());

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, int $id)
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();

        return back()->with('success', 'Kategori berhasil dipulihkan.');
    }

    public function export(Request $request)
    {
        $search  = trim((string) $request->query('search', ''));
        $status  = (string) $request->query('status', 'all');
        $trashed = (string) $request->query('trashed', 'without');

        return Excel::download(
            new CategoriesExport($search, $status, $trashed),
            'kategori.xlsx'
        );
    }
}
