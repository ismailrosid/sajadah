<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Sholat - SAJADA</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #059669;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #065f46;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 11px;
            color: #666;
            margin: 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
        }
        .meta-table td {
            padding: 3px 0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .badge-present {
            color: #059669;
            font-weight: bold;
        }
        .badge-late {
            color: #d97706;
            font-weight: bold;
        }
        .badge-rejected {
            color: #dc2626;
            font-weight: bold;
        }
        .footer {
            margin-top: 25px;
            text-align: right;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">SAJADA &bull; Laporan Kehadiran Sholat</h1>
        <p class="subtitle">Aplikasi Personal Prayer Attendance &amp; Tracker</p>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;"><strong>Periode Laporan:</strong> {{ $startDate }} s/d {{ $endDate }}</td>
            <td style="width: 50%; text-align: right;"><strong>Waktu Cetak:</strong> {{ $generatedAt }}</td>
        </tr>
        <tr>
            <td><strong>Total Data:</strong> {{ count($attendances) }} catatan</td>
            <td style="text-align: right;"><strong>Aplikasi:</strong> SAJADA</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 25%;">Nama Anggota</th>
                <th style="width: 12%;">Waktu Sholat</th>
                <th style="width: 10%;">Jam Server</th>
                <th style="width: 18%;">Masjid</th>
                <th style="width: 8%; text-align: right;">Jarak</th>
                <th style="width: 10%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $a)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $a->attendance_date->format('d/m/Y') }}</td>
                <td><strong>{{ $a->user->name }}</strong></td>
                <td>{{ $a->prayer_type->label() }}</td>
                <td>{{ $a->server_timestamp->format('H:i') }}</td>
                <td>{{ $a->mosque?->name ?? '-' }}</td>
                <td style="text-align: right;">{{ round($a->distance_meters) }}m</td>
                <td style="text-align: center;">
                    @if($a->status->value === 'present')
                        <span class="badge-present">Tepat Waktu</span>
                    @elseif($a->status->value === 'late')
                        <span class="badge-late">Terlambat</span>
                    @else
                        <span class="badge-rejected">Ditolak</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">
                    Tidak ada data absensi pada periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak secara otomatis oleh Sistem SAJADA &bull; Build Consistency, Strengthen Community
    </div>
</body>
</html>
