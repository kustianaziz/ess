<?php

namespace App\Http\Controllers;

use App\Actions\Shared\GenerateRequestNumberAction;
use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\ActivityType;
use App\Models\Approval;
use App\Models\Attachment;
use App\Models\OperationalRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OperationalController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user()->load('division');
        $activityTypes = ActivityType::where('is_active', true)->get();

        return Inertia::render('Pengajuan/Operasional/Create', [
            'applicant' => [
                'name' => $user->name,
                'nik' => $user->nik,
                'division' => $user->division?->name ?? '-',
                'position' => $user->position ?? '-',
                'submission_date' => now()->translatedFormat('d F Y'),
            ],
            'activityTypes' => $activityTypes,
        ]);
    }

    public function store(Request $request, GenerateRequestNumberAction $generateNumber, RecordStatusHistoryAction $recordHistory): RedirectResponse
    {
        $isDraft = $request->input('action') === 'draft';

        $validated = $request->validate([
            'activity_type_id' => $isDraft ? 'nullable|exists:activity_types,id' : 'required|exists:activity_types,id',
            'activity_date' => $isDraft ? 'nullable|date' : 'required|date',
            'activity_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'purpose' => $isDraft ? 'nullable|string' : 'required|string',
            'participant_count' => $isDraft ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'estimated_cost' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:1000',
            'location' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $validated, $user, $isDraft, $generateNumber, $recordHistory) {
            $status = $isDraft ? RequestStatus::DRAFT->value : RequestStatus::SUBMITTED->value;
            $requestNumber = $generateNumber->execute('KO', 'operational_requests');

            $operational = OperationalRequest::create([
                'request_number' => $requestNumber,
                'user_id' => $user->id,
                'activity_type_id' => $validated['activity_type_id'],
                'activity_date' => $validated['activity_date'],
                'activity_name' => $validated['activity_name'] ?? '',
                'purpose' => $validated['purpose'] ?? '',
                'participant_count' => $validated['participant_count'] ?? 1,
                'estimated_cost' => $validated['estimated_cost'] ?? 0,
                'location' => $validated['location'] ?? '',
                'status' => $status,
                'current_approval_level' => $isDraft ? 0 : 1,
                'submitted_at' => $isDraft ? null : now(),
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/operational', 'public');
                    Attachment::create([
                        'attachable_type' => OperationalRequest::class,
                        'attachable_id' => $operational->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            // Record status history
            $recordHistory->execute(
                $operational,
                null,
                $status,
                $user->id,
                $isDraft ? 'Pengajuan disimpan sebagai draf.' : 'Pengajuan dikirim untuk persetujuan.'
            );

            // Create initial approval records if submitted
            if (!$isDraft) {
                app(\App\Actions\Shared\CreateInitialApprovalAction::class)->execute($operational, $user, 'operasional');
            }

            $message = $isDraft ? 'Pengajuan konsumsi/operasional disimpan sebagai draft.' : 'Pengajuan konsumsi/operasional berhasil dikirim!';
            return redirect()->route('riwayat-pengajuan.index')->with('success', $message);
        });
    }
}
