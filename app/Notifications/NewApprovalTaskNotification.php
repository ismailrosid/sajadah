<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewApprovalTaskNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Attendance $attendance,
        public int $level = 1
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_approval_task',
            'title' => 'Tugas Persetujuan Absensi Baru',
            'message' => "Ada absensi Sholat {$this->attendance->prayer_type->label()} dari {$this->attendance->user->name} tanggal {$this->attendance->attendance_date->format('d M Y')} yang memerlukan persetujuan Anda (Tingkat {$this->level}).",
            'attendance_id' => $this->attendance->id,
            'attendance_date' => $this->attendance->attendance_date->toDateString(),
            'user_name' => $this->attendance->user->name,
            'prayer_label' => $this->attendance->prayer_type->label(),
        ];
    }
}
