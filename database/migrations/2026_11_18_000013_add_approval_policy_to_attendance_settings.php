<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_settings', 'auto_approve_ontime')) {
                $table->boolean('auto_approve_ontime')->default(true)->after('require_gps');
            }
            if (!Schema::hasColumn('attendance_settings', 'auto_approve_after_hours')) {
                $table->integer('auto_approve_after_hours')->default(24)->after('auto_approve_ontime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_settings', 'auto_approve_after_hours')) {
                $table->dropColumn('auto_approve_after_hours');
            }
            if (Schema::hasColumn('attendance_settings', 'auto_approve_ontime')) {
                $table->dropColumn('auto_approve_ontime');
            }
        });
    }
};
