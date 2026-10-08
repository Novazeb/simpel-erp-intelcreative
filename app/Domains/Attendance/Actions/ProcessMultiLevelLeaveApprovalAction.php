<?php

namespace App\Domains\Attendance\Actions;

use App\Domains\Attendance\Models\LeaveApprovalStep;
use App\Domains\Attendance\Models\LeaveRequest;
use App\Domains\HR\Models\Employee;
use Illuminate\Support\Facades\DB;

class ProcessMultiLevelLeaveApprovalAction
{
    /**
     * Memproses persetujuan cuti berjenjang:
     * Step 1: Supervisor
     * Step 2: Head of Department (HOD)
     * Step 3: HR Final Approval
     */
    public function approve(LeaveRequest $leaveRequest, Employee $approver, ?string $note = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $note) {
            // Dapatkan step aktif yang masih PENDING
            $currentStep = $leaveRequest->steps()
                ->where('status', 'PENDING')
                ->orderBy('step_level', 'asc')
                ->first();

            if (! $currentStep) {
                return $leaveRequest;
            }

            $currentStep->update([
                'approver_id' => $approver->id,
                'status' => 'APPROVED',
                'note' => $note,
                'action_at' => now(),
            ]);

            // Cek apakah masih ada step selanjutnya
            $nextStep = $leaveRequest->steps()
                ->where('status', 'PENDING')
                ->where('step_level', '>', $currentStep->step_level)
                ->orderBy('step_level', 'asc')
                ->first();

            if ($nextStep) {
                // Perbarui status request ke level berikutnya
                $statusMap = [
                    2 => 'PENDING_HOD',
                    3 => 'PENDING_HR',
                ];
                $leaveRequest->update([
                    'status' => $statusMap[$nextStep->step_level] ?? 'PENDING',
                ]);
            } else {
                // Semua approval level selesai -> status APPROVED
                $leaveRequest->update([
                    'status' => 'APPROVED',
                    'approved_by' => $approver->user_id,
                    'approved_at' => now(),
                ]);
            }

            return $leaveRequest->fresh();
        });
    }

    public function reject(LeaveRequest $leaveRequest, Employee $approver, string $reason): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $reason) {
            $currentStep = $leaveRequest->steps()
                ->where('status', 'PENDING')
                ->orderBy('step_level', 'asc')
                ->first();

            if ($currentStep) {
                $currentStep->update([
                    'approver_id' => $approver->id,
                    'status' => 'REJECTED',
                    'note' => $reason,
                    'action_at' => now(),
                ]);
            }

            $leaveRequest->update([
                'status' => 'REJECTED',
            ]);

            return $leaveRequest->fresh();
        });
    }
}

