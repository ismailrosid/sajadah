<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Attendance $attendance,
        public ?User $approver = null,
        public ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $approverName = $this->approver ? $this->approver->name : 'Pengurus';
        return [
            'type' => 'attendance_approved',
            'title' => 'Absensi Disetujui',
            'message' => "Absensi Sholat {$this->attendance->prayer_type->label()} Anda tanggal {$this->attendance->attendance_date->format('d M Y')} telah disetujui oleh {$approverName}." . ($this->notes ? " Catatan: {$this->notes}" : ''),
            'attendance_id' => $this->attendance->id,
            'attendance_date' => $this->attendance->attendance_date->toDateString(),
            'prayer_label' => $this->attendance->prayer_type->label(),
        ];
    }
}
