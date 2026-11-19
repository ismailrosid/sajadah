<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update communities table with reference location
        Schema::table('communities', function (Blueprint $table) {
            $table->string('reference_city')->nullable()->after('address');
            $table->decimal('reference_latitude', 10, 8)->nullable()->after('reference_city');
            $table->decimal('reference_longitude', 11, 8)->nullable()->after('reference_latitude');
        });

        // 2. Update attendance_settings with attendance_duration_minutes
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->unsignedInteger('attendance_duration_minutes')->default(60)->after('default_radius_meters');
        });

        // 3. Update attendances table with approval fields and pending_approval status
        // In MySQL, modify ENUM column to include 'pending_approval'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'rejected', 'absent', 'pending_approval') NOT NULL DEFAULT 'present'");
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->text('request_notes')->nullable()->after('rejection_reason');
            $table->foreignId('approved_by')->nullable()->after('request_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['request_notes', 'approved_by', 'approved_at', 'approval_notes']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'rejected', 'absent') NOT NULL DEFAULT 'present'");
        }

        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn('attendance_duration_minutes');
        });

        Schema::table('communities', function (Blueprint $table) {
            $table->dropColumn(['reference_city', 'reference_latitude', 'reference_longitude']);
        });
    }
};
