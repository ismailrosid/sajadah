<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceAutoApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Attendance $attendance
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'attendance_auto_approved',
            'title' => 'Absensi Disetujui Otomatis oleh Sistem',
            'message' => "Absensi Sholat {$this->attendance->prayer_type->label()} Anda tanggal {$this->attendance->attendance_date->format('d M Y')} telah disetujui otomatis oleh sistem karena waktu sholat telah terlewati.",
            'attendance_id' => $this->attendance->id,
            'attendance_date' => $this->attendance->attendance_date->toDateString(),
            'prayer_label' => $this->attendance->prayer_type->label(),
        ];
    }
}
