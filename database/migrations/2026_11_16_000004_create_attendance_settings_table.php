<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('default_radius_meters')->default(100);
            $table->integer('opening_buffer_minutes')->default(30)->comment('Buka absensi X menit sebelum adzan');
            $table->integer('closing_buffer_minutes')->default(45)->comment('Tutup absensi X menit setelah adzan');
            $table->integer('late_tolerance_minutes')->default(15)->comment('Toleransi menit setelah adzan sebelum dianggap late');
            $table->string('prayer_calc_method', 50)->default('KEMENAG');
            $table->boolean('is_attendance_enabled')->default(true);
            $table->boolean('require_selfie')->default(true);
            $table->boolean('require_gps')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
