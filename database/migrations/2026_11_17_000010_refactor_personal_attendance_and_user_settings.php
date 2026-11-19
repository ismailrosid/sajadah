<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add personal prayer preferences directly to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable()->default('Jakarta');
            }
            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->default(-6.20880000);
            }
            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->default(106.84560000);
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 50)->default('Asia/Jakarta');
            }
            if (!Schema::hasColumn('users', 'prayer_calc_method')) {
                $table->string('prayer_calc_method', 50)->default('KEMENAG');
            }
            if (!Schema::hasColumn('users', 'attendance_duration_minutes')) {
                $table->unsignedSmallInteger('attendance_duration_minutes')->default(60);
            }
        });

        // 2. Make community_id and mosque_id nullable in attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->change();
            $table->foreignId('mosque_id')->nullable()->change();
        });

        // Drop the old community-based composite unique constraint if exists
        try {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropUnique('unique_user_community_prayer_daily');
            });
        } catch (\Throwable $e) {
            // Ignore if key doesn't exist
        }

        // Add personal unique constraint: 1 user, 1 prayer type, 1 date
        try {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unique(['user_id', 'prayer_type', 'attendance_date'], 'unique_user_prayer_daily');
            });
        } catch (\Throwable $e) {
            // Ignore if already added
        }

        // 3. Make community_id and mosque_id nullable in prayer_schedules table
        Schema::table('prayer_schedules', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->change();
            $table->foreignId('mosque_id')->nullable()->change();

            if (!Schema::hasColumn('prayer_schedules', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('prayer_schedules', 'city')) {
                $table->string('city', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'latitude',
                'longitude',
                'timezone',
                'prayer_calc_method',
                'attendance_duration_minutes',
            ]);
        });

        Schema::table('prayer_schedules', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'city']);
        });
    }
};
