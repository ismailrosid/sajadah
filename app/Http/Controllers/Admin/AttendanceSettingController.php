<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSettingController extends Controller
{
    public function edit(Request $request): Response
    {
        $setting = AttendanceSetting::getGlobalSetting();

        return Inertia::render('Admin/Settings/Index', [
            'setting' => $setting,
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

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_duration_minutes' => ['required', 'integer', 'min:10', 'max:180'],
            'prayer_calc_method' => ['required', 'string', 'in:KEMENAG,MWL,EGYPT,ISNA,MAKKAH'],
            'is_attendance_enabled' => ['required', 'boolean'],
            'require_selfie' => ['required', 'boolean'],
            'require_gps' => ['required', 'boolean'],
            'auto_approve_ontime' => ['nullable', 'boolean'],
            'auto_approve_after_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        $setting = AttendanceSetting::getGlobalSetting();
        $setting->update([
            'attendance_duration_minutes' => $validated['attendance_duration_minutes'],
            'prayer_calc_method' => $validated['prayer_calc_method'],
            'is_attendance_enabled' => $validated['is_attendance_enabled'],
            'require_selfie' => $validated['require_selfie'],
            'require_gps' => $validated['require_gps'],
            'auto_approve_ontime' => $request->boolean('auto_approve_ontime', true),
            'auto_approve_after_hours' => $validated['auto_approve_after_hours'] ?? 24,
        ]);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
