<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
            $table->enum('prayer_type', ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'])->index();
            $table->date('attendance_date')->index();
            $table->string('photo_path')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('gps_accuracy', 8, 2)->nullable();
            $table->decimal('distance_meters', 8, 2)->nullable();
            $table->timestamp('server_timestamp');
            $table->enum('status', ['present', 'late', 'rejected', 'absent'])->default('present')->index();
            $table->string('rejection_reason')->nullable();
            $table->unsignedTinyInteger('risk_score')->default(0)->comment('0 (aman) - 100 (curiga/anomali)');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'community_id', 'prayer_type', 'attendance_date'], 'unique_user_community_prayer_daily');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
