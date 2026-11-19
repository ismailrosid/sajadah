<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearAttendanceDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:clear {--keep-approver-config : Keep user approver configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus semua data riwayat absensi, approval, log, dan notifikasi tanpa menghapus user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->confirm('Apakah Anda yakin ingin menghapus data absensi & riwayat approval? (Data Users & Akun Admin TIDAK akan dihapus)', true)) {
            $this->info('Operasi dibatalkan.');
            return Command::SUCCESS;
        }

        $this->info('Membersihkan data absensi dan riwayat transaksi...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'attendance_approvals',
            'approval_histories',
            'attendances',
            'activity_logs',
            'notifications',
        ];

        if (!$this->option('keep-approver-config')) {
            // Tanyakan apakah konfigurasi atasan/approver ingin di-reset juga
            // Default: pertahankan konfigurasi jika user tidak memilih
        }

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line(" <info>✓</info> Tabel <comment>{$table}</comment> berhasil dikosongkan.");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info('Semua data absensi dan approval berhasil dibersihkan! Data user tetap aman.');

        return Command::SUCCESS;
    }
}
