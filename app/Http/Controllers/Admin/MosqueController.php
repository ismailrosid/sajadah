<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mosque;
use App\Models\PrayerSchedule;
use App\Services\Community\CommunityContextService;
use App\Services\Prayer\PrayerScheduleCalculationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MosqueController extends Controller
{
    public function __construct(
        protected CommunityContextService $contextService,
        protected PrayerScheduleCalculationService $prayerService
    ) {}

    public function index(Request $request): Response
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if (!$community) {
            abort(404, 'Komunitas tidak ditemukan.');
        }

        $mosques = Mosque::where('community_id', $community->id)
            ->withCount(['attendances'])
            ->latest()
            ->paginate(10)
            ->through(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'address' => $m->address,
                'latitude' => $m->latitude,
                'longitude' => $m->longitude,
                'radius_meters' => $m->radius_meters,
                'is_active' => $m->is_active,
                'is_default' => $m->is_default,
                'attendances_count' => $m->attendances_count,
                'description' => $m->description,
            ]);

        return Inertia::render('Admin/Mosques/Index', [
            'mosques' => $mosques,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if (!$community) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:20', 'max:1000'],
            'is_default' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!empty($validated['is_default'])) {
            Mosque::where('community_id', $community->id)->update(['is_default' => false]);
        } else {
            // If first mosque in community, automatically make it default
            $hasMosques = Mosque::where('community_id', $community->id)->exists();
            if (!$hasMosques) {
                $validated['is_default'] = true;
            }
        }

        $mosque = Mosque::create([
            ...$validated,
            'community_id' => $community->id,
            'is_active' => true,
        ]);

        // Auto-generate prayer schedules for this new mosque for the current month
        $this->generateSchedulesForMosque($mosque, $community->setting?->prayer_calc_method ?? 'KEMENAG');

        return back()->with('success', "Masjid {$mosque->name} berhasil ditambahkan dan jadwal sholat telah digenerate.");
    }

    public function update(Request $request, Mosque $mosque): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if ($mosque->community_id !== $community->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:20', 'max:1000'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!empty($validated['is_default'])) {
            Mosque::where('community_id', $community->id)->where('id', '!=', $mosque->id)->update(['is_default' => false]);
        }

        $mosque->update($validated);

        // Regenerate schedules if coordinates changed
        $this->generateSchedulesForMosque($mosque, $community->setting?->prayer_calc_method ?? 'KEMENAG');

        return back()->with('success', "Data masjid {$mosque->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, Mosque $mosque): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if ($mosque->community_id !== $community->id) {
            abort(403);
        }

        $name = $mosque->name;
        $mosque->delete();

        return back()->with('success', "Masjid {$name} berhasil dihapus.");
    }

    protected function generateSchedulesForMosque(Mosque $mosque, string $method): void
    {
        $today = Carbon::today();
        for ($i = -2; $i <= 30; $i++) {
            $date = $today->copy()->addDays($i);
            $times = $this->prayerService->calculate(
                $mosque->latitude,
                $mosque->longitude,
                $date,
                $method
            );

            PrayerSchedule::updateOrCreate(
                [
                    'mosque_id' => $mosque->id,
                    'schedule_date' => $date->toDateString(),
                ],
                [
                    'community_id' => $mosque->community_id,
                    'fajr' => $times['fajr'],
                    'dhuhr' => $times['dhuhr'],
                    'asr' => $times['asr'],
                    'maghrib' => $times['maghrib'],
                    'isha' => $times['isha'],
                    'calculation_method' => $method,
                ]
            );
        }
    }
}
