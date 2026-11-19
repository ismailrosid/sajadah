<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable()->default('Jakarta')->after('phone');
            }
            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->default(-6.20880000)->after('city');
            }
            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->default(106.84560000)->after('latitude');
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 50)->default('Asia/Jakarta')->after('longitude');
            }
            if (!Schema::hasColumn('users', 'prayer_calc_method')) {
                $table->string('prayer_calc_method', 50)->default('KEMENAG')->after('timezone');
            }
            if (!Schema::hasColumn('users', 'attendance_duration_minutes')) {
                $table->unsignedSmallInteger('attendance_duration_minutes')->default(60)->after('prayer_calc_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            foreach (['city', 'latitude', 'longitude', 'timezone', 'prayer_calc_method', 'attendance_duration_minutes'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
