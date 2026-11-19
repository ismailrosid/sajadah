<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
            $table->date('schedule_date')->index();
            $table->time('fajr');
            $table->time('dhuhr');
            $table->time('asr');
            $table->time('maghrib');
            $table->time('isha');
            $table->string('calculation_method', 50)->default('KEMENAG');
            $table->timestamps();

            $table->unique(['mosque_id', 'schedule_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_schedules');
    }
};
