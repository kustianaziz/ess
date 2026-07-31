<?php

namespace App\Actions\Shared;

use App\Models\StatusHistory;
use Illuminate\Database\Eloquent\Model;

class RecordStatusHistoryAction
{
    public function execute(Model $model, ?string $fromStatus, string $toStatus, ?int $changedById = null, ?string $notes = null): StatusHistory
    {
        return StatusHistory::create([
            'trackable_type' => get_class($model),
            'trackable_id' => $model->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $changedById,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
