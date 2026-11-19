<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'userSettings' => [
                'city' => $user->getCity(),
                'latitude' => $user->getLatitude(),
                'longitude' => $user->getLongitude(),
                'timezone' => $user->getTimezone(),
                'prayer_calc_method' => $user->getPrayerCalcMethod(),
                'attendance_duration_minutes' => $user->getAttendanceDurationMinutes(),
            ],
            'calculationMethods' => [
                ['value' => 'KEMENAG', 'label' => 'Kementerian Agama RI (Kemenag)'],
                ['value' => 'MWL', 'label' => 'Muslim World League (MWL)'],
                ['value' => 'EGYPT', 'label' => 'Egyptian General Authority of Survey'],
                ['value' => 'ISNA', 'label' => 'Islamic Society of North America (ISNA)'],
                ['value' => 'MAKKAH', 'label' => 'Umm Al-Qura University, Makkah'],
            ],
            'timezones' => [
                ['value' => 'Asia/Jakarta', 'label' => 'Asia/Jakarta (WIB - UTC+7)'],
                ['value' => 'Asia/Makassar', 'label' => 'Asia/Makassar (WITA - UTC+8)'],
                ['value' => 'Asia/Jayapura', 'label' => 'Asia/Jayapura (WIT - UTC+9)'],
                ['value' => 'UTC', 'label' => 'UTC (Universal Coordinated Time)'],
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
