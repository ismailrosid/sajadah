<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Table approval_roles: Master role for approval categories (Staff, Supervisor, Manager, etc.)
        if (!Schema::hasTable('approval_roles')) {
            Schema::create('approval_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default approval roles
            DB::table('approval_roles')->insert([
                ['name' => 'Staff', 'description' => 'Tingkatan staf / anggota', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Supervisor', 'description' => 'Pengawas / koordinator tingkat pertama', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Manager', 'description' => 'Manajer / pimpinan tingkat menengah', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Head Department', 'description' => 'Kepala departemen / pimpinan divisi', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 2. Table approval_processes: Configuration mapping of each user to approver(s) and level
        if (!Schema::hasTable('approval_processes')) {
            Schema::create('approval_processes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('approval_role_id')->nullable()->constrained('approval_roles')->nullOnDelete();
                $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('approval_level')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index(['approver_id', 'is_active']);
            });
        }

        // 3. Table attendance_approvals: Transactions and history of approvals
        if (!Schema::hasTable('attendance_approvals')) {
            Schema::create('attendance_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('approval_level')->default(1);
                $table->string('status', 30)->default('pending')->index(); // pending, approved, rejected
                $table->string('approval_source', 30)->nullable(); // approver, system
                $table->text('note')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->index(['attendance_id', 'approval_level']);
                $table->index(['approver_id', 'status']);
            });
        }

        // 4. Update attendances table with approval_source and expanded status enum
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'rejected', 'absent', 'pending_approval', 'pending', 'approved') NOT NULL DEFAULT 'present'");
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'approval_source')) {
                $table->string('approval_source', 30)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_approvals');
        Schema::dropIfExists('approval_processes');
        Schema::dropIfExists('approval_roles');

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'approval_source')) {
                $table->dropColumn('approval_source');
            }
        });
    }
};
