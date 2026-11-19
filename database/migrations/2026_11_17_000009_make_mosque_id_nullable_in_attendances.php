<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('mosque_id')->nullable()->change();
        });

        Schema::table('prayer_schedules', function (Blueprint $table) {
            $table->foreignId('mosque_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'mosque_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('mosque_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('prayer_schedules') && Schema::hasColumn('prayer_schedules', 'mosque_id')) {
            Schema::table('prayer_schedules', function (Blueprint $table) {
                $table->foreignId('mosque_id')->nullable(false)->change();
            });
        }
    }
};
