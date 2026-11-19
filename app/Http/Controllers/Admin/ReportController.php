<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $startDate = $request->query('start_date', Carbon::today()->subDays(6)->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());
        $prayerType = $request->query('prayer_type');
        $status = $request->query('status');
        $userId = $request->query('user_id');

        $query = Attendance::with(['user', 'approver'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($prayerType && in_array($prayerType, ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'])) {
            $query->where('prayer_type', $prayerType);
        }

        if ($status && in_array($status, ['present', 'late', 'rejected', 'pending'])) {
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'pending_approval']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = (clone $query)->latest('server_timestamp')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($a) => [
                'id' => $a->id,
                'user_name' => $a->user?->name ?? 'User',
                'user_email' => $a->user?->email,
                'prayer_label' => $a->prayer_type->label(),
                'attendance_date' => $a->attendance_date->format('d M Y'),
                'time' => $a->server_timestamp->format('H:i:s'),
                'distance_meters' => $a->distance_meters,
                'gps_accuracy' => $a->gps_accuracy,
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'rejection_reason' => $a->rejection_reason,
                'has_photo' => !empty($a->photo_path),
                'photo_url' => !empty($a->photo_path) ? route('member.attendance.photo', $a->id) : null,
            ]);

        // Aggregate statistics for the filtered range
        $allFiltered = (clone $query)->get();
        $total = $allFiltered->count();
        $present = $allFiltered->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Approved])->count();
        $late = $allFiltered->where('status', AttendanceStatus::Late)->count();
        $rejected = $allFiltered->where('status', AttendanceStatus::Rejected)->count();
        $pending = $allFiltered->whereIn('status', [AttendanceStatus::Pending, AttendanceStatus::PendingApproval])->count();

        $users = User::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Reports/Index', [
            'attendances' => $attendances,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'prayer_type' => $prayerType,
                'status' => $status,
                'user_id' => $userId,
            ],
            'stats' => [
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'rejected' => $rejected,
                'pending' => $pending,
            ],
            'members' => $users,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $startDate = $request->query('start_date', Carbon::today()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $attendances = Attendance::with('user')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->latest('server_timestamp')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Absensi');

        // Header Title
        $sheet->setCellValue('A1', 'LAPORAN KEHADIRAN SHOLAT - SAJADA');
        $sheet->setCellValue('A2', "Periode: {$startDate} s/d {$endDate} | Dicetak: " . now()->format('d M Y H:i'));
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        // Table Headers
        $headers = ['No', 'Tanggal', 'Nama Pengguna', 'Waktu Sholat', 'Jam Server', 'Lokasi (GPS)', 'Status'];
        $columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue("{$columnLetters[$index]}4", $header);
        }

        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data Rows
        $row = 5;
        foreach ($attendances as $index => $att) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $att->attendance_date->format('Y-m-d'));
            $sheet->setCellValue("C{$row}", $att->user?->name ?? 'User');
            $sheet->setCellValue("D{$row}", $att->prayer_type->label());
            $sheet->setCellValue("E{$row}", $att->server_timestamp->format('H:i:s'));
            $sheet->setCellValue("F{$row}", "{$att->latitude}, {$att->longitude}");
            $sheet->setCellValue("G{$row}", $att->status->label());

            $row++;
        }

        foreach ($columnLetters as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Laporan_Absensi_SAJADA_{$startDate}_{$endDate}.xlsx";

        return response()->stream(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::today()->subDays(14)->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $attendances = Attendance::with('user')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->latest('server_timestamp')
            ->limit(500)
            ->get();

        $pdf = Pdf::loadView('reports.attendance_pdf', [
            'attendances' => $attendances,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now()->format('d M Y H:i:s'),
        ])->setPaper('a4', 'portrait');

        $filename = "Laporan_Absensi_SAJADA_{$startDate}.pdf";

        return $pdf->download($filename);
    }
}
