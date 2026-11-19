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

        if ($driver === 'mysql') {
            // Helper function to safely drop foreign keys
            $dropFk = function ($table, $fkName) {
                try {
                    $exists = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = '{$table}' 
                          AND CONSTRAINT_NAME = '{$fkName}'
                    ");
                    if (!empty($exists)) {
                        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
                    }
                } catch (\Throwable $e) {}
            };

            // Helper function to safely drop index
            $dropIdx = function ($table, $idxName) {
                try {
                    $exists = DB::select("
                        SHOW INDEX FROM `{$table}` WHERE Key_name = '{$idxName}'
                    ");
                    if (!empty($exists)) {
                        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$idxName}`");
                    }
                } catch (\Throwable $e) {}
            };

            // 1. Drop foreign keys and indexes from attendances
            if (Schema::hasTable('attendances')) {
                $dropFk('attendances', 'attendances_community_id_foreign');
                $dropFk('attendances', 'attendances_mosque_id_foreign');
                $dropIdx('attendances', 'unique_user_community_prayer_daily');
                $dropIdx('attendances', 'attendances_community_id_foreign');
                $dropIdx('attendances', 'attendances_mosque_id_foreign');

                if (Schema::hasColumn('attendances', 'community_id')) {
                    DB::statement("ALTER TABLE `attendances` DROP COLUMN `community_id`");
                }
                if (Schema::hasColumn('attendances', 'mosque_id')) {
                    DB::statement("ALTER TABLE `attendances` DROP COLUMN `mosque_id`");
                }
            }

            // 2. Drop foreign keys and indexes from prayer_schedules
            if (Schema::hasTable('prayer_schedules')) {
                $dropFk('prayer_schedules', 'prayer_schedules_community_id_foreign');
                $dropFk('prayer_schedules', 'prayer_schedules_mosque_id_foreign');
                $dropIdx('prayer_schedules', 'prayer_schedules_mosque_id_schedule_date_unique');
                $dropIdx('prayer_schedules', 'prayer_schedules_community_id_foreign');
                $dropIdx('prayer_schedules', 'prayer_schedules_mosque_id_foreign');

                if (Schema::hasColumn('prayer_schedules', 'community_id')) {
                    DB::statement("ALTER TABLE `prayer_schedules` DROP COLUMN `community_id`");
                }
                if (Schema::hasColumn('prayer_schedules', 'mosque_id')) {
                    DB::statement("ALTER TABLE `prayer_schedules` DROP COLUMN `mosque_id`");
                }
            }

            // 3. Drop foreign keys from activity_logs
            if (Schema::hasTable('activity_logs')) {
                $dropFk('activity_logs', 'activity_logs_community_id_foreign');
                $dropIdx('activity_logs', 'activity_logs_community_id_foreign');
                if (Schema::hasColumn('activity_logs', 'community_id')) {
                    DB::statement("ALTER TABLE `activity_logs` DROP COLUMN `community_id`");
                }
            }

            // 4. Drop community_id from attendance_settings
            if (Schema::hasTable('attendance_settings') && Schema::hasColumn('attendance_settings', 'community_id')) {
                $dropFk('attendance_settings', 'attendance_settings_community_id_foreign');
                $dropIdx('attendance_settings', 'attendance_settings_community_id_unique');
                $dropIdx('attendance_settings', 'attendance_settings_community_id_foreign');
                DB::statement("ALTER TABLE `attendance_settings` DROP COLUMN `community_id`");
            }

            // 5. Drop unused tables
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Schema::dropIfExists('community_invitations');
            Schema::dropIfExists('community_members');
            Schema::dropIfExists('mosques');
            Schema::dropIfExists('communities');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            // SQLite (In-memory testing database): No need to drop columns as SQLite cannot alter columns with FKs
        }
    }

    public function down(): void
    {
        // Decommissioned
    }
};
