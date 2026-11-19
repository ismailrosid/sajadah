<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Community;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function index(): Response
    {
        $communities = Community::withCount(['members', 'mosques', 'attendances'])
            ->latest()
            ->paginate(10)
            ->through(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'address' => $c->address,
                'timezone' => $c->timezone,
                'status' => $c->status,
                'members_count' => $c->members_count,
                'mosques_count' => $c->mosques_count,
                'attendances_count' => $c->attendances_count,
                'created_at' => $c->created_at->format('d M Y'),
            ]);

        return Inertia::render('SuperAdmin/Communities/Index', [
            'communities' => $communities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'timezone' => ['required', 'string', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura,UTC'],
        ]);

        Community::create($validated);

        return back()->with('success', 'Komunitas berhasil ditambahkan.');
    }

    public function update(Request $request, Community $community): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'timezone' => ['required', 'string', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura,UTC'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $community->update($validated);

        return back()->with('success', 'Data komunitas berhasil diperbarui.');
    }

    public function destroy(Community $community): RedirectResponse
    {
        $community->delete();

        return back()->with('success', 'Komunitas berhasil dihapus.');
    }
}
