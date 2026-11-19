<?php

namespace App\Console\Commands;

use App\Services\Approval\AttendanceApprovalService;
use Illuminate\Console\Command;

class AutoApproveAttendanceCommand extends Command
{
    protected $signature = 'attendance:auto-approve';
    protected $description = 'Automatically approve pending attendances whose prayer window has expired (approval_source = system)';

    public function handle(AttendanceApprovalService $approvalService): int
    {
        $this->info('Starting automated attendance approval process...');

        $approvedCount = $approvalService->autoApproveExpiredPendingAttendances();

        $this->info("Completed. Total attendances auto-approved by system: {$approvedCount}");

        return Command::SUCCESS;
    }
}
