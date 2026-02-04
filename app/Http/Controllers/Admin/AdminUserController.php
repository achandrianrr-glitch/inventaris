<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserStoreRequest;
use App\Http\Requests\Admin\AdminUserUpdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string)$request->query('search', '')),
            'status' => (string)$request->query('status', 'all'), // all|active|inactive
        ];

        $q = User::query()
            ->select(['id', 'name', 'email', 'status', 'last_login', 'created_at', 'deleted_at'])
            ->when($filters['search'] !== '', function (Builder $x) use ($filters) {
                $s = $filters['search'];
                $x->where(function (Builder $w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->when(in_array($filters['status'], ['active', 'inactive'], true), fn($x) => $x->where('status', $filters['status']))
            ->orderByDesc('id')
            ->withTrashed(); // supaya bisa lihat yang soft deleted

        $users = $q->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function store(AdminUserStoreRequest $request)
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
        ]);

        return back()->with('success', 'Admin baru berhasil ditambahkan.');
    }

    public function update(AdminUserUpdateRequest $request, User $user)
    {
        if ($user->trashed()) {
            return back()->withErrors(['update' => 'User sudah dihapus (soft delete). Restore dulu jika ingin edit.']);
        }

        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'status' => $data['status'],
        ]);

        return back()->with('success', 'Data admin berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        if ($user->trashed()) {
            return back()->withErrors(['reset' => 'User sudah dihapus (soft delete). Restore dulu jika ingin reset password.']);
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return back()->with('success', 'Password berhasil direset.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->trashed()) {
            return back()->withErrors(['toggle' => 'User sudah dihapus (soft delete). Restore dulu jika ingin ubah status.']);
        }

        // jangan boleh menonaktifkan diri sendiri (aman)
        if ((int)$user->id === (int)auth()->id()) {
            throw ValidationException::withMessages([
                'toggle' => 'Tidak bisa menonaktifkan akun yang sedang dipakai login.',
            ]);
        }

        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();

        return back()->with('success', 'Status admin berhasil diubah.');
    }

    public function destroy(User $user)
    {
        // jangan boleh hapus diri sendiri
        if ((int)$user->id === (int)auth()->id()) {
            throw ValidationException::withMessages([
                'delete' => 'Tidak bisa menghapus akun yang sedang dipakai login.',
            ]);
        }

        $user->delete();

        return back()->with('success', 'Admin berhasil dihapus.');
    }

    public function restore(User $user)
    {
        $user->restore();
        return back()->with('success', 'Admin berhasil direstore.');
    }
}
