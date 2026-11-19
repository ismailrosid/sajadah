<?php

use App\Http\Controllers\Admin\ApprovalConfigurationController;
use App\Http\Controllers\Admin\AttendanceApprovalController;
use App\Http\Controllers\Admin\AttendanceMonitoringController;
use App\Http\Controllers\Admin\AttendanceSettingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Member\AttendanceController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\HistoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User / Member Core Routes
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('member.attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('member.attendance.store');
    Route::get('/attendance/photo/{attendance}', [AttendanceController::class, 'photo'])->name('member.attendance.photo');
    Route::get('/history', [HistoryController::class, 'index'])->name('member.history.index');

    // Dynamic Approval Routes (Accessible to Admins and Users who are assigned Approvers)
    Route::middleware('approver')->group(function () {
        Route::get('/approvals', [AttendanceApprovalController::class, 'index'])->name('approvals.index');
        Route::get('/approvals/daily/{date}', [AttendanceApprovalController::class, 'dailyDetail'])->name('approvals.daily');
        Route::get('/approvals/verify/{attendance}', [AttendanceApprovalController::class, 'show'])->name('approvals.show');
        Route::post('/approvals/{attendance}/approve', [AttendanceApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{attendance}/reject', [AttendanceApprovalController::class, 'reject'])->name('approvals.reject');

        // Aliases for admin.* routes so all existing Vue views work seamlessly for both roles
        Route::get('/admin/approvals', [AttendanceApprovalController::class, 'index'])->name('admin.approvals.index');
        Route::get('/admin/approvals/daily/{date}', [AttendanceApprovalController::class, 'dailyDetail'])->name('admin.approvals.daily');
        Route::get('/admin/approvals/verify/{attendance}', [AttendanceApprovalController::class, 'show'])->name('admin.approvals.show');
        Route::post('/admin/attendances/{attendance}/approve', [AttendanceApprovalController::class, 'approve'])->name('admin.attendances.approve');
        Route::post('/admin/attendances/{attendance}/reject', [AttendanceApprovalController::class, 'reject'])->name('admin.attendances.reject');
    });

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // Admin Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // User Management
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Dynamic Approval Management & Configuration
        Route::get('/approval-config', [ApprovalConfigurationController::class, 'index'])->name('approval-config.index');
        Route::post('/approval-config/roles', [ApprovalConfigurationController::class, 'storeRole'])->name('approval-config.roles.store');
        Route::put('/approval-config/roles/{role}', [ApprovalConfigurationController::class, 'updateRole'])->name('approval-config.roles.update');
        Route::delete('/approval-config/roles/{role}', [ApprovalConfigurationController::class, 'destroyRole'])->name('approval-config.roles.destroy');
        Route::post('/approval-config/processes', [ApprovalConfigurationController::class, 'storeProcess'])->name('approval-config.processes.store');
        Route::put('/approval-config/processes/{process}', [ApprovalConfigurationController::class, 'updateProcess'])->name('approval-config.processes.update');
        Route::delete('/approval-config/processes/{process}', [ApprovalConfigurationController::class, 'destroyProcess'])->name('approval-config.processes.destroy');

        // Attendance Monitoring
        Route::get('/attendances', [AttendanceMonitoringController::class, 'index'])->name('attendances.index');

        // Application Settings
        Route::get('/settings', [AttendanceSettingController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [AttendanceSettingController::class, 'update'])->name('settings.update');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    });
});

require __DIR__.'/auth.php';
