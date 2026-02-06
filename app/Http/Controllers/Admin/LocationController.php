<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LocationStoreRequest;
use App\Http\Requests\Admin\LocationUpdateRequest;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        $search  = trim((string) $request->query('search', ''));
        $status  = (string) $request->query('status', 'all');
        $trashed = (string) $request->query('trashed', 'without'); // without | with | only

        $query = Location::query()
            ->select(['id', 'name', 'description', 'status', 'created_at', 'updated_at', 'deleted_at'])
            ->withCount('items');

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

        $locations = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Locations/Index', [
            'locations' => $locations,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'trashed' => $trashed,
            ],
        ]);
    }

    public function store(LocationStoreRequest $request)
    {
        $data = $request->validated();

        $location = Location::create($data);

        // LOG AKTIVITAS
        activity_log('locations', 'create', "Tambah lokasi: {$location->name} (ID: {$location->id})");

        return back()->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(LocationUpdateRequest $request, Location $location)
    {
        $beforeName = $location->name;

        $location->update($request->validated());

        // LOG AKTIVITAS
        $afterName = $location->name;
        $desc = ($beforeName !== $afterName)
            ? "Update lokasi ID {$location->id}: {$beforeName} → {$afterName}"
            : "Update lokasi: {$location->name} (ID: {$location->id})";

        activity_log('locations', 'update', $desc);

        return back()->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location)
    {
        $name = $location->name;
        $id   = $location->id;

        $location->delete();

        // LOG AKTIVITAS
        activity_log('locations', 'delete', "Soft delete lokasi: {$name} (ID: {$id})");

        return back()->with('success', 'Lokasi berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, int $id)
    {
        $location = Location::withTrashed()->findOrFail($id);
        $location->restore();

        // LOG AKTIVITAS
        activity_log('locations', 'restore', "Restore lokasi: {$location->name} (ID: {$location->id})");

        return back()->with('success', 'Lokasi berhasil dipulihkan.');
    }
}
