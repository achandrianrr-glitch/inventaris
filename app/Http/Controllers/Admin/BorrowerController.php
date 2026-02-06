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
        $data = $request->validated();

        $borrower = Borrower::create($data);

        // LOG AKTIVITAS
        $idNumber = $borrower->id_number ? " | ID: {$borrower->id_number}" : "";
        activity_log(
            'borrowers',
            'create',
            "Tambah peminjam: {$borrower->name} (ID: {$borrower->id}) | type={$borrower->type}{$idNumber}"
        );

        return back()->with('success', 'Peminjam berhasil ditambahkan.');
    }

    public function update(BorrowerUpdateRequest $request, Borrower $borrower)
    {
        $before = [
            'name' => $borrower->name,
            'type' => $borrower->type,
            'status' => $borrower->status,
            'id_number' => $borrower->id_number,
        ];

        $borrower->update($request->validated());

        $after = [
            'name' => $borrower->name,
            'type' => $borrower->type,
            'status' => $borrower->status,
            'id_number' => $borrower->id_number,
        ];

        // LOG AKTIVITAS
        $changes = [];
        foreach ($before as $k => $v) {
            if (($after[$k] ?? null) !== $v) {
                $changes[] = "{$k}: " . ($v ?? '-') . " → " . ($after[$k] ?? '-');
            }
        }

        $desc = "Update peminjam: {$borrower->name} (ID: {$borrower->id})";
        if (!empty($changes)) {
            $desc .= " | " . implode(' | ', $changes);
        }

        activity_log('borrowers', 'update', $desc);

        return back()->with('success', 'Peminjam berhasil diperbarui.');
    }

    public function destroy(Borrower $borrower)
    {
        $name = $borrower->name;
        $id   = $borrower->id;
        $type = $borrower->type;
        $idNumber = $borrower->id_number ? " | ID: {$borrower->id_number}" : "";

        $borrower->delete();

        // LOG AKTIVITAS
        activity_log('borrowers', 'delete', "Soft delete peminjam: {$name} (ID: {$id}) | type={$type}{$idNumber}");

        return back()->with('success', 'Peminjam berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, int $id)
    {
        $borrower = Borrower::withTrashed()->findOrFail($id);
        $borrower->restore();

        // LOG AKTIVITAS
        $idNumber = $borrower->id_number ? " | ID: {$borrower->id_number}" : "";
        activity_log('borrowers', 'restore', "Restore peminjam: {$borrower->name} (ID: {$borrower->id}) | type={$borrower->type}{$idNumber}");

        return back()->with('success', 'Peminjam berhasil dipulihkan.');
    }
}
