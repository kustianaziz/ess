<?php

namespace App\Actions\Shared;

use App\Models\Approval;
use App\Models\User;
use App\Notifications\RequestSubmittedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class CreateInitialApprovalAction
{
    /**
     * Handles initial approval creation upon request submission.
     * If applicant is a Manager / Level 1 Atasan, Level 1 is AUTO-APPROVED and forwarded to HRD (Level 2).
     */
    public function execute(Model $model, User $applicant, string $type): void
    {
        $isLevel1Atasan = $applicant->hasRole('manager')
            || $applicant->hasRole('admin')
            || $applicant->hasRole('hrd_finance')
            || empty($applicant->manager_id)
            || $applicant->manager_id === $applicant->id;

        if ($isLevel1Atasan) {
            // 1. Auto-approve Level 1
            Approval::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approver_id' => $applicant->id,
                'level' => 1,
                'status' => 'approved',
                'notes' => 'Otomatis disetujui (Pemohon adalah Atasan Level 1).',
                'acted_at' => now(),
            ]);

            // 2. Fetch HRD/Finance users
            $hrdUsers = User::role('hrd_finance')->get();
            if ($hrdUsers->isEmpty()) {
                $hrdUsers = User::role('admin')->get();
            }
            $firstHrd = $hrdUsers->first() ?? $applicant;

            // 3. Create Level 2 Pending Approval
            Approval::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approver_id' => $firstHrd->id,
                'level' => 2,
                'status' => 'pending',
            ]);

            // 4. Update request current approval level to 2
            $model->update(['current_approval_level' => 2]);

            // 5. Notify all HRD users
            if ($hrdUsers->isNotEmpty()) {
                Notification::send($hrdUsers, new RequestSubmittedNotification($type, $model->id, $model->request_number, $applicant->name));
            }
        } else {
            // Applicant has a Direct Manager -> Level 1 Pending Approval
            $managerId = $applicant->manager_id;

            Approval::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approver_id' => $managerId,
                'level' => 1,
                'status' => 'pending',
            ]);

            $model->update(['current_approval_level' => 1]);

            $manager = User::find($managerId);
            if ($manager) {
                $manager->notify(new RequestSubmittedNotification($type, $model->id, $model->request_number, $applicant->name));
            }
        }
    }
}
