<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use App\Models\ActivityLog;
use App\Models\ApprovalProcess;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\PrayerSchedule;
use App\Models\User;
use App\Services\Approval\AttendanceApprovalService;
use App\Services\Prayer\PrayerScheduleCalculationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceValidationService
{
    public function __construct(
        protected HaversineDistanceCalculator $distanceCalculator,
        protected PrayerScheduleCalculationService $prayerService
    ) {}

    /**
     * Process and validate prayer attendance submission.
     * Supports both process(User, data, photo) and legacy process(User, community, data, photo).
     */
    public function process(
        User $user,
        mixed $communityOrData = [],
        mixed $dataOrPhoto = null,
        ?UploadedFile $photoFile = null
    ): Attendance {
        if (is_array($communityOrData)) {
            $data = $communityOrData;
            $photoFile = $dataOrPhoto instanceof UploadedFile ? $dataOrPhoto : $photoFile;
        } else {
            $data = is_array($dataOrPhoto) ? $dataOrPhoto : [];
        }

        $passedCommunity = ($communityOrData instanceof \App\Models\Community)
            ? $communityOrData
            : null;

        return DB::transaction(function () use ($user, $data, $photoFile, $passedCommunity) {
            $globalSetting = AttendanceSetting::getGlobalSetting();

            if ($globalSetting && !$globalSetting->is_attendance_enabled) {
                throw ValidationException::withMessages([
                    'prayer_type' => 'Sistem absensi sedang dinonaktifkan.',
                ]);
            }

            $prayerType = PrayerType::from($data['prayer_type']);

            $timezone = $user->getTimezone();
            $serverTimestamp = Carbon::now($timezone);
            $todayDate = $serverTimestamp->toDateString();
            $isTestMode = filter_var(env('ATTENDANCE_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN);

            // 1. Check duplicate attendance for this prayer and user today (Bypassed in test mode)
            $existing = Attendance::where('user_id', $user->id)
                ->where('prayer_type', $prayerType->value)
                ->whereDate('attendance_date', $todayDate)
                ->first();

            if (!$isTestMode && $existing) {
                // Jika statusnya adalah 'absent' (otomatis sistem karena lewat waktu) dan sekarang mengajukan susulan,
                // izinkan untuk menimpa/mengupdate record absent tersebut.
                if ($existing->status === AttendanceStatus::Absent && $isLateRequest) {
                    // Diizinkan: record akan di-update ke pengajuan susulan
                } else {
                    throw ValidationException::withMessages([
                        'prayer_type' => "Absensi Sholat {$prayerType->label()} hari ini sudah tercatat.",
                    ]);
                }
            }

            // 2. Obtain verified personal prayer schedule for today
            $schedule = $this->prayerService->getScheduleForUser($user, $todayDate);

            if (!$schedule && !$isTestMode) {
                throw ValidationException::withMessages([
                    'prayer_type' => 'Jadwal sholat belum tersedia.',
                ]);
            }

            $prayerTimeStr = $schedule?->getTimeFor($prayerType->value) ?? '04:15:00';
            if (!$prayerTimeStr && !$isTestMode) {
                throw ValidationException::withMessages([
                    'prayer_type' => "Jadwal Sholat {$prayerType->label()} tidak ditemukan.",
                ]);
            }

            // 3. Automatic Window Evaluation
            // Opens when prayer time enters, closes after $durationMinutes
            $durationMinutes = $user->getAttendanceDurationMinutes() ?? $globalSetting->attendance_duration_minutes ?? 60;
            $windowStart = Carbon::parse("{$todayDate} {$prayerTimeStr}", $timezone);
            $windowEnd = $windowStart->copy()->addMinutes($durationMinutes);

            $isLateRequest = !empty($data['is_late_request']);
            $requestNotes = $data['request_notes'] ?? null;

            // Check if user has active approval processes
            $hasApprover = ApprovalProcess::where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();

            // Status: Jika ada approver -> pending approval. Jika tidak ada approver -> langsung disetujui (auto approve)
            if ($hasApprover) {
                $status = $isLateRequest ? AttendanceStatus::PendingApproval : AttendanceStatus::Pending;
            } else {
                $status = $isLateRequest ? AttendanceStatus::Late : AttendanceStatus::Present;
            }

            $rejectionReason = null;
            $riskScore = 0;

            // Check if prayer time is in future (Bypassed in test mode)
            if (!$isTestMode && $serverTimestamp->lt($windowStart)) {
                throw ValidationException::withMessages([
                    'prayer_type' => "Waktu Sholat {$prayerType->label()} belum masuk (buka pukul {$windowStart->format('H:i')}).",
                ]);
            }

            // Check if window has ended (Bypassed in test mode)
            if (!$isTestMode && $serverTimestamp->gt($windowEnd)) {
                if (!$isLateRequest) {
                    throw ValidationException::withMessages([
                        'prayer_type' => 'Waktu absensi normal telah berakhir. Silakan ajukan absensi susulan.',
                        'is_window_closed' => true,
                    ]);
                }

                $status = $hasApprover ? AttendanceStatus::PendingApproval : AttendanceStatus::Late;
            }

            // 4. Catat Titik Lokasi GPS Kehadiran Aktual
            $userLat = (float) $data['latitude'];
            $userLng = (float) $data['longitude'];
            $gpsAccuracy = isset($data['gps_accuracy']) ? (float) $data['gps_accuracy'] : null;

            $distanceMeters = null;
            if ($user->latitude && $user->longitude) {
                $distanceMeters = $this->distanceCalculator->calculate(
                    $userLat,
                    $userLng,
                    $user->getLatitude(),
                    $user->getLongitude()
                );
            }

            if ($gpsAccuracy && $gpsAccuracy > 100) {
                $riskScore += 25;
            }
            if ($isLateRequest) {
                $riskScore += 20;
            }

            // 5. Handle Selfie Upload (Saved to private storage)
            $photoPath = null;
            if ($photoFile) {
                $filename = (string) Str::uuid() . '.' . $photoFile->getClientOriginalExtension();
                $photoPath = $photoFile->storeAs('private/attendances/' . $user->id, $filename, 'local');
            } elseif ($isTestMode) {
                $photoPath = 'private/attendances/' . $user->id . '/test_selfie.jpg';
                if (!Storage::disk('local')->exists($photoPath) || Storage::disk('local')->size($photoPath) < 500) {
                    Storage::disk('local')->put(
                        $photoPath,
                        $this->generateDummySelfie($user->name)
                    );
                }
            } elseif ($status !== AttendanceStatus::Rejected) {
                throw ValidationException::withMessages([
                    'photo' => 'Foto selfie kamera wajib disertakan.',
                ]);
            }

            // 6. Save Attendance Record
            $attendanceData = [
                'user_id' => $user->id,
                'prayer_type' => $prayerType,
                'attendance_date' => $todayDate,
                'photo_path' => $photoPath,
                'latitude' => $userLat,
                'longitude' => $userLng,
                'gps_accuracy' => $gpsAccuracy,
                'distance_meters' => $distanceMeters,
                'server_timestamp' => $serverTimestamp,
                'status' => $status,
                'rejection_reason' => $rejectionReason,
                'request_notes' => $requestNotes,
                'risk_score' => $riskScore,
                'metadata' => [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'location_name' => $data['location_name'] ?? null,
                    'address' => $data['address'] ?? null,
                    'window_start' => $windowStart->format('H:i:s'),
                    'window_end' => $windowEnd->format('H:i:s'),
                    'is_late_request' => $isLateRequest,
                ],
            ];

            if (Schema::hasColumn('attendances', 'community_id') && ($data['community_id'] ?? $passedCommunity?->id)) {
                $attendanceData['community_id'] = $data['community_id'] ?? $passedCommunity?->id;
            }
            if (Schema::hasColumn('attendances', 'mosque_id') && ($data['mosque_id'] ?? null)) {
                $attendanceData['mosque_id'] = $data['mosque_id'] ?? null;
            }

            $existing = Attendance::where('user_id', $user->id)
                ->where('prayer_type', $prayerType->value)
                ->whereDate('attendance_date', $todayDate)
                ->first();

            if ($existing) {
                $existing->approvals()->delete();
                $existing->update($attendanceData);
                $attendance = $existing;
            } else {
                $attendance = Attendance::create($attendanceData);
            }

            // 7. Initialize Dynamic Approval Workflow
            app(AttendanceApprovalService::class)->initializeApproval($attendance);
            $attendance->refresh();

            // 8. Log Activity
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $isLateRequest ? 'record_late_attendance' : 'record_attendance',
                'subject_type' => Attendance::class,
                'subject_id' => $attendance->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => [
                    'prayer' => $prayerType->value,
                    'status' => $attendance->status->value ?? $status->value,
                    'distance' => $distanceMeters,
                    'is_late_request' => $isLateRequest,
                ],
            ]);

            return $attendance;
        });
    }

    /**
     * Admin approves a late attendance request.
     */
    public function approveLateRequest(
        Attendance $attendance,
        User $admin,
        ?string $notes = null
    ): Attendance {
        return DB::transaction(function () use ($attendance, $admin, $notes) {
            $attendance->update([
                'status' => AttendanceStatus::Late,
                'approval_source' => 'admin',
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'approve_late_request',
                'subject_type' => Attendance::class,
                'subject_id' => $attendance->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => [
                    'prayer' => $attendance->prayer_type->value,
                    'attendance_date' => $attendance->attendance_date->toDateString(),
                    'notes' => $notes,
                ],
            ]);

            return $attendance;
        });
    }

    /**
     * Admin rejects a late attendance request.
     */
    public function rejectLateRequest(
        Attendance $attendance,
        User $admin,
        string $reason
    ): Attendance {
        return DB::transaction(function () use ($attendance, $admin, $reason) {
            $attendance->update([
                'status' => AttendanceStatus::Rejected,
                'approval_source' => 'admin',
                'rejection_reason' => $reason,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'reject_late_request',
                'subject_type' => Attendance::class,
                'subject_id' => $attendance->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => [
                    'prayer' => $attendance->prayer_type->value,
                    'attendance_date' => $attendance->attendance_date->toDateString(),
                    'reason' => $reason,
                ],
            ]);

            return $attendance;
        });
    }

    /**
     * Generate fallback dummy selfie image for testing mode when camera is not used.
     */
    protected function generateDummySelfie(string $userName = 'User Test'): string
    {
        if (extension_loaded('gd')) {
            $width = 640;
            $height = 640;
            $img = imagecreatetruecolor($width, $height);

            $bg = imagecolorallocate($img, 15, 23, 42); // slate-900
            imagefilledrectangle($img, 0, 0, $width, $height, $bg);

            $circleBg = imagecolorallocate($img, 16, 185, 129); // emerald-500
            imagefilledellipse($img, 320, 260, 220, 220, $circleBg);

            $silhouette = imagecolorallocate($img, 255, 255, 255);
            imagefilledellipse($img, 320, 220, 90, 100, $silhouette);
            imagefilledarc($img, 320, 340, 160, 140, 180, 360, $silhouette, IMG_ARC_PIE);

            $textColor = imagecolorallocate($img, 248, 250, 252);
            $subTextColor = imagecolorallocate($img, 148, 163, 184);

            $title = "Foto Selfie (Uji Coba)";
            $subtitle = "Mode Testing - Absensi Tanpa Kamera";
            $userText = "Nama: " . $userName;

            imagestring($img, 5, (int)(320 - (strlen($title) * 9 / 2)), 430, $title, $textColor);
            imagestring($img, 4, (int)(320 - (strlen($subtitle) * 8 / 2)), 470, $subtitle, $subTextColor);
            imagestring($img, 4, (int)(320 - (strlen($userText) * 8 / 2)), 505, $userText, $subTextColor);

            ob_start();
            imagejpeg($img, null, 90);
            $data = ob_get_clean();
            imagedestroy($img);

            return $data;
        }

        return base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
    }
}
