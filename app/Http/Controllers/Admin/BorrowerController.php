<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BorrowerStoreRequest;
use App\Http\Requests\Admin\BorrowerUpdateRequest;
use App\Models\Borrower;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BorrowerController extends Controller
{
    public function index(Request $request): Response
    {
        $search  = trim((string) $request->query('search', ''));
        $type    = (string) $request->query('type', 'all');        // all | student | teacher
        $class   = trim((string) $request->query('class', ''));    // free text (opsional)
        $status  = (string) $request->query('status', 'all');      // all | active | blocked
        $trashed = (string) $request->query('trashed', 'without'); // without | with | only

        $query = Borrower::query()
            ->select(['id', 'name', 'type', 'class', 'major', 'id_number', 'contact', 'status', 'created_at', 'updated_at', 'deleted_at']);

        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")
                    ->orWhere('major', 'like', "%{$search}%")
                    ->orWhere('class', 'like', "%{$search}%");
            });
        }

        if (in_array($type, ['student', 'teacher'], true)) {
            $query->where('type', $type);
        }

        if ($class !== '') {
            $query->where('class', 'like', "%{$class}%");
        }

        if (in_array($status, ['active', 'blocked'], true)) {
            $query->where('status', $status);
        }

        $borrowers = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Borrowers/Index', [
            'borrowers' => $borrowers,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'class' => $class,
                'status' => $status,
                'trashed' => $trashed,
            ],
        ]);
    }

    public function store(BorrowerStoreRequest $request)
    {
        Borrower::create($request->validated());

        return back()->with('success', 'Peminjam berhasil ditambahkan.');
    }

    public function update(BorrowerUpdateRequest $request, Borrower $borrower)
    {
        $borrower->update($request->validated());

        return back()->with('success', 'Peminjam berhasil diperbarui.');
    }

    public function destroy(Borrower $borrower)
    {
        $borrower->delete();

        return back()->with('success', 'Peminjam berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, int $id)
    {
        $borrower = Borrower::withTrashed()->findOrFail($id);
        $borrower->restore();

        return back()->with('success', 'Peminjam berhasil dipulihkan.');
    }
}
