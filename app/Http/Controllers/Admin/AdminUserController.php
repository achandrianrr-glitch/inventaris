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

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
        ]);

        // LOG AKTIVITAS
        activity_log('users', 'create', "Tambah admin: {$user->email} (ID: {$user->id}) | status={$user->status}");

        return back()->with('success', 'Admin baru berhasil ditambahkan.');
    }

    public function update(AdminUserUpdateRequest $request, User $user)
    {
        if ($user->trashed()) {
            return back()->withErrors(['update' => 'User sudah dihapus (soft delete). Restore dulu jika ingin edit.']);
        }

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
        ];

        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'status' => $data['status'],
        ]);

        $after = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
        ];

        $changes = [];
        foreach ($before as $k => $v) {
            if (($after[$k] ?? null) !== $v) {
                $changes[] = "{$k}: " . ($v ?? '-') . " → " . ($after[$k] ?? '-');
            }
        }

        $desc = "Update admin: {$user->email} (ID: {$user->id})";
        if (!empty($changes)) {
            $desc .= " | " . implode(' | ', $changes);
        }

        // LOG AKTIVITAS
        activity_log('users', 'update', $desc);

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

        // LOG AKTIVITAS
        activity_log('users', 'reset', "Reset password: {$user->email} (ID: {$user->id})");

        return back()->with('success', 'Password berhasil direset.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->trashed()) {
            return back()->withErrors(['toggle' => 'User sudah dihapus (soft delete). Restore dulu jika ingin ubah status.']);
        }

        $currentAdminId = (int) $request->user()->id;

        // jangan boleh menonaktifkan diri sendiri (aman)
        if ((int)$user->id === $currentAdminId) {
            throw ValidationException::withMessages([
                'toggle' => 'Tidak bisa menonaktifkan akun yang sedang dipakai login.',
            ]);
        }

        $before = $user->status;

        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();

        // LOG AKTIVITAS
        activity_log('users', 'toggle', "Toggle status: {$user->email} (ID: {$user->id}) | {$before} → {$user->status}");

        return back()->with('success', 'Status admin berhasil diubah.');
    }

    public function destroy(Request $request, User $user)
    {
        $currentAdminId = (int) $request->user()->id;

        // jangan boleh hapus diri sendiri
        if ((int)$user->id === $currentAdminId) {
            throw ValidationException::withMessages([
                'delete' => 'Tidak bisa menghapus akun yang sedang dipakai login.',
            ]);
        }

        $email = $user->email;
        $id = $user->id;

        $user->delete();

        // LOG AKTIVITAS
        activity_log('users', 'delete', "Soft delete admin: {$email} (ID: {$id})");

        return back()->with('success', 'Admin berhasil dihapus.');
    }

    public function restore(User $user)
    {
        // aman kalau belum trashed
        if ($user->trashed()) {
            $user->restore();

            // LOG AKTIVITAS
            activity_log('users', 'restore', "Restore admin: {$user->email} (ID: {$user->id})");
        }

        return back()->with('success', 'Admin berhasil direstore.');
    }
}
