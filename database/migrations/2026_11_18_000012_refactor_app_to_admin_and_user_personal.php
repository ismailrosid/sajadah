<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. In attendance_settings: make community_id nullable and decouple from unique constraint
        if (Schema::hasTable('attendance_settings')) {
            if ($driver === 'mysql') {
                // Check if foreign key exists and drop it
                $fkExists = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'attendance_settings' 
                      AND COLUMN_NAME = 'community_id' 
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                if (!empty($fkExists)) {
                    $constraintName = $fkExists[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE attendance_settings DROP FOREIGN KEY `{$constraintName}`");
                }

                // Check if unique index exists and drop it
                $indexExists = DB::select("
                    SHOW INDEX FROM attendance_settings WHERE Column_name = 'community_id' AND Non_unique = 0
                ");
                if (!empty($indexExists)) {
                    $keyName = $indexExists[0]->Key_name;
                    DB::statement("ALTER TABLE attendance_settings DROP INDEX `{$keyName}`");
                }

                // Alter column to be nullable
                DB::statement("ALTER TABLE attendance_settings MODIFY community_id BIGINT UNSIGNED NULL");
            } else {
                // SQLite
                Schema::table('attendance_settings', function (Blueprint $table) {
                    $table->unsignedBigInteger('community_id')->nullable()->change();
                });
            }

            // Ensure a global default settings row exists where community_id is NULL
            $globalSettingExists = DB::table('attendance_settings')->whereNull('community_id')->exists();
            if (!$globalSettingExists) {
                $existing = DB::table('attendance_settings')->first();
                DB::table('attendance_settings')->insert([
                    'community_id' => null,
                    'default_radius_meters' => $existing?->default_radius_meters ?? 100,
                    'attendance_duration_minutes' => $existing?->attendance_duration_minutes ?? 60,
                    'opening_buffer_minutes' => $existing?->opening_buffer_minutes ?? 30,
                    'closing_buffer_minutes' => $existing?->closing_buffer_minutes ?? 45,
                    'late_tolerance_minutes' => $existing?->late_tolerance_minutes ?? 15,
                    'prayer_calc_method' => $existing?->prayer_calc_method ?? 'KEMENAG',
                    'is_attendance_enabled' => $existing?->is_attendance_enabled ?? true,
                    'require_selfie' => $existing?->require_selfie ?? true,
                    'require_gps' => $existing?->require_gps ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Ensure global_role in users table supports 'admin', 'super_admin', and 'user' with default 'user'
        if (Schema::hasTable('users')) {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE users MODIFY global_role VARCHAR(50) NOT NULL DEFAULT 'user'");
            } else {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('global_role', 50)->default('user')->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Non-destructive rollback
    }
};
