<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use Illuminate\Http\Request;

use Inertia\Inertia;
use Inertia\Response;

class RequestHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $statusFilter = $request->input('status');
        $typeFilter = $request->input('type');
        $search = $request->input('search');

        $requestsCollection = collect();

        // 1. Reimbursements
        if (!$typeFilter || $typeFilter === 'reimbursement') {
            $query = ReimbursementRequest::with(['expenseType', 'user.division'])
                ->where('user_id', $user->id);

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            foreach ($query->get() as $item) {
                $requestsCollection->push([
                    'id' => $item->id,
                    'type' => 'reimbursement',
                    'type_label' => 'Reimbursement Karyawan',
                    'request_number' => $item->request_number,
                    'category' => $item->expenseType?->name ?? 'Pengeluaran',
                    'date' => $item->expense_date->format('Y-m-d'),
                    'amount' => (float) $item->amount,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'status_color' => $item->status->colorClass(),
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ]);
            }
        }

        // 2. Operational
        if (!$typeFilter || $typeFilter === 'operasional') {
            $query = OperationalRequest::with(['activityType', 'user.division'])
                ->where('user_id', $user->id);

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhere('activity_name', 'like', "%{$search}%")
                      ->orWhere('purpose', 'like', "%{$search}%");
                });
            }

            foreach ($query->get() as $item) {
                $requestsCollection->push([
                    'id' => $item->id,
                    'type' => 'operasional',
                    'type_label' => 'Konsumsi / Operasional',
                    'request_number' => $item->request_number,
                    'category' => $item->activityType?->name ?? 'Kegiatan',
                    'date' => $item->activity_date->format('Y-m-d'),
                    'amount' => (float) $item->estimated_cost,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'status_color' => $item->status->colorClass(),
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ]);
            }
        }

        // 3. Leave
        if (!$typeFilter || $typeFilter === 'cuti') {
            $query = LeaveRequest::with(['leaveType', 'user.division'])
                ->where('user_id', $user->id);

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhere('reason', 'like', "%{$search}%");
                });
            }

            foreach ($query->get() as $item) {
                $requestsCollection->push([
                    'id' => $item->id,
                    'type' => 'cuti',
                    'type_label' => 'Cuti Karyawan',
                    'request_number' => $item->request_number,
                    'category' => $item->leaveType?->name ?? 'Cuti',
                    'date' => $item->start_date->format('Y-m-d'),
                    'amount' => (float) $item->total_days,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'status_color' => $item->status->colorClass(),
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ]);
            }
        }

        // 4. Perjalanan Dinas
        if (!$typeFilter || $typeFilter === 'perjalanan-dinas') {
            $query = \App\Models\BusinessTripRequest::with(['user.division', 'settlement', 'assignee'])
                ->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('assigned_to', $user->id);
                });

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhere('destination', 'like', "%{$search}%")
                      ->orWhere('purpose', 'like', "%{$search}%");
                });
            }

            foreach ($query->get() as $item) {
                $requestsCollection->push([
                    'id' => $item->id,
                    'type' => 'perjalanan-dinas',
                    'type_label' => 'Perjalanan Dinas',
                    'request_number' => $item->request_number,
                    'category' => 'Dinas Ke ' . $item->destination,
                    'date' => $item->start_date->format('Y-m-d'),
                    'amount' => (float) $item->estimated_budget,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'status_color' => $item->status->colorClass(),
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                    'has_settlement' => (bool) $item->settlement,
                ]);
            }
        }

        // 5. Rencana Lembur (Tahap 1)
        if (!$typeFilter || $typeFilter === 'lembur') {
            $query = \App\Models\OvertimeRequest::with(['user.division'])
                ->where('user_id', $user->id);

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhere('task_description', 'like', "%{$search}%");
                });
            }

            foreach ($query->get() as $item) {
                $requestsCollection->push([
                    'id' => $item->id,
                    'type' => 'lembur',
                    'type_label' => 'Rencana Lembur',
                    'request_number' => $item->request_number,
                    'category' => 'Lembur ' . $item->date->format('d M'),
                    'date' => $item->date->format('Y-m-d'),
                    'amount' => 0,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'status_color' => $item->status->colorClass(),
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ]);
            }
        }

        // 6. Pencairan Lembur (Tahap 2)
        if (!$typeFilter || $typeFilter === 'klaim-lembur') {
            $query = \App\Models\OvertimeClaim::with(['user.division'])
                ->where('user_id', $user->id);

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('claim_number', 'like', "%{$search}%");
                });
            }

            foreach ($query->get() as $item) {
                $requestsCollection->push([
                    'id' => $item->id,
                    'type' => 'klaim-lembur',
                    'type_label' => 'Klaim Lembur',
                    'request_number' => $item->claim_number,
                    'category' => 'Pencairan Lembur',
                    'date' => $item->created_at->format('Y-m-d'),
                    'amount' => (float) $item->amount,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'status_color' => $item->status->colorClass(),
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ]);
            }
        }

        $sorted = $requestsCollection->sortByDesc('created_at')->values();

        return Inertia::render('RiwayatPengajuan/Index', [
            'requests' => $sorted,
            'filters' => [
                'status' => $statusFilter ?? '',
                'type' => $typeFilter ?? '',
                'search' => $search ?? '',
            ],
        ]);
    }

    public function show(string $type, int $id): Response
    {
        $requestData = null;

        if ($type === 'reimbursement') {
            $item = ReimbursementRequest::with(['user.division', 'expenseType', 'attachments', 'approvals.approver', 'statusHistories.changedBy', 'paidBy'])
                ->findOrFail($id);
            $requestData = [
                'id' => $item->id,
                'type' => 'reimbursement',
                'type_label' => 'Reimbursement Karyawan',
                'request_number' => $item->request_number,
                'applicant' => [
                    'name' => $item->user->name,
                    'nik' => $item->user->nik,
                    'division' => $item->user->division?->name ?? '-',
                    'position' => $item->user->position ?? '-',
                ],
                'details' => array_filter([
                    'Jenis Pengeluaran' => $item->expenseType?->name,
                    'Tanggal Pengeluaran' => $item->expense_date->translatedFormat('d F Y'),
                    'Nominal' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                    'Keterangan' => $item->description,
                    'Nomor Referensi Pembayaran' => $item->payment_reference,
                    'Waktu Pembayaran' => $item->paid_at ? $item->paid_at->translatedFormat('d F Y H:i') : null,
                    'Diproses Oleh' => $item->paidBy?->name,
                ]),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'rejected_reason' => $item->rejected_reason,
                'attachments' => $item->attachments,
                'approvals' => $item->approvals,
                'status_histories' => $item->statusHistories,
                'created_at' => $item->created_at->translatedFormat('d F Y H:i'),
            ];
        } elseif ($type === 'operasional') {
            $item = OperationalRequest::with(['user.division', 'activityType', 'attachments', 'approvals.approver', 'statusHistories.changedBy', 'paidBy'])
                ->findOrFail($id);
            $requestData = [
                'id' => $item->id,
                'type' => 'operasional',
                'type_label' => 'Konsumsi / Operasional',
                'request_number' => $item->request_number,
                'applicant' => [
                    'name' => $item->user->name,
                    'nik' => $item->user->nik,
                    'division' => $item->user->division?->name ?? '-',
                    'position' => $item->user->position ?? '-',
                ],
                'details' => array_filter([
                    'Jenis Kegiatan' => $item->activityType?->name,
                    'Nama Kegiatan' => $item->activity_name,
                    'Tanggal Kegiatan' => $item->activity_date->translatedFormat('d F Y'),
                    'Jumlah Peserta' => $item->participant_count . ' Orang',
                    'Estimasi Biaya' => 'Rp ' . number_format($item->estimated_cost, 0, ',', '.'),
                    'Lokasi' => $item->location,
                    'Tujuan / Keterangan' => $item->purpose,
                    'Nomor Referensi Pembayaran' => $item->payment_reference,
                    'Waktu Pembayaran' => $item->paid_at ? $item->paid_at->translatedFormat('d F Y H:i') : null,
                    'Diproses Oleh' => $item->paidBy?->name,
                ]),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'rejected_reason' => $item->rejected_reason,
                'attachments' => $item->attachments,
                'approvals' => $item->approvals,
                'status_histories' => $item->statusHistories,
                'created_at' => $item->created_at->translatedFormat('d F Y H:i'),
            ];
        } elseif ($type === 'cuti') {
            $item = LeaveRequest::with(['user.division', 'leaveType', 'handoverToUser', 'attachments', 'approvals.approver', 'statusHistories.changedBy'])
                ->findOrFail($id);
            $requestData = [
                'id' => $item->id,
                'type' => 'cuti',
                'type_label' => 'Cuti Karyawan',
                'request_number' => $item->request_number,
                'applicant' => [
                    'name' => $item->user->name,
                    'nik' => $item->user->nik,
                    'division' => $item->user->division?->name ?? '-',
                    'position' => $item->user->position ?? '-',
                ],
                'details' => [
                    'Jenis Cuti' => $item->leaveType?->name,
                    'Tanggal Mulai' => $item->start_date->translatedFormat('d F Y'),
                    'Tanggal Selesai' => $item->end_date->translatedFormat('d F Y'),
                    'Total Hari' => $item->total_days . ' Hari Kerja',
                    'Alasan Cuti' => $item->reason,
                    'Serah Terima Pekerjaan Kepada' => $item->handoverToUser?->name ?? '-',
                    'Catatan Serah Terima' => $item->handover_notes ?? '-',
                ],
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'rejected_reason' => $item->rejected_reason,
                'attachments' => $item->attachments,
                'approvals' => $item->approvals,
                'status_histories' => $item->statusHistories,
                'created_at' => $item->created_at->translatedFormat('d F Y H:i'),
            ];
        } elseif ($type === 'perjalanan-dinas') {
            $item = \App\Models\BusinessTripRequest::with(['user.division', 'attachments', 'approvals.approver', 'statusHistories.changedBy', 'settlement.expenseItems', 'settlement.attachments', 'assignee'])
                ->findOrFail($id);
            $requestData = [
                'id' => $item->id,
                'type' => 'perjalanan-dinas',
                'type_label' => 'Perjalanan Dinas',
                'request_number' => $item->request_number,
                'applicant' => [
                    'name' => $item->user->name,
                    'nik' => $item->user->nik,
                    'division' => $item->user->division?->name ?? '-',
                    'position' => $item->user->position ?? '-',
                ],
                'details' => array_filter([
                    'Tipe Pengajuan' => $item->is_delegated ? 'Penugasan Tim' : 'Pengajuan Sendiri',
                    'Ditugaskan Kepada' => $item->is_delegated ? $item->assignee?->name : null,
                    'Kota / Tujuan' => $item->destination,
                    'Instansi / Perusahaan Tujuan' => $item->target_institution,
                    'Tujuan Kegiatan' => $item->purpose,
                    'Tanggal Berangkat' => $item->start_date->translatedFormat('d F Y'),
                    'Tanggal Kembali' => $item->end_date->translatedFormat('d F Y'),
                    'Moda Transportasi' => $item->transportation_type ?? 'Pesawat / Kendaraan Umum',
                    'No. Surat Tugas' => $item->assignment_letter_number,
                    'Estimasi Uang Muka' => 'Rp ' . number_format($item->estimated_budget, 0, ',', '.'),
                    'Nominal Uang Muka Dicairkan' => $item->disbursed_budget ? 'Rp ' . number_format($item->disbursed_budget, 0, ',', '.') : null,
                    'Nomor Referensi Pembayaran' => $item->payment_reference,
                    'Waktu Pembayaran' => $item->paid_at ? $item->paid_at->translatedFormat('d F Y H:i') : null,
                    'Diproses Oleh' => $item->paidBy?->name,
                ]),
                'disbursed_budget' => $item->disbursed_budget ? (float)$item->disbursed_budget : null,
                'allowance_breakdown' => $item->allowance_breakdown ?? [],
                'settlement' => $item->settlement ? [
                    'id' => $item->settlement->id,
                    'settlement_number' => $item->settlement->settlement_number,
                    'total_actual_cost' => 'Rp ' . number_format($item->settlement->total_actual_cost, 0, ',', '.'),
                    'advance_amount' => 'Rp ' . number_format($item->settlement->advance_amount, 0, ',', '.'),
                    'difference_amount' => ($item->settlement->difference_amount >= 0 ? '+ Rp ' : '- Rp ') . number_format(abs($item->settlement->difference_amount), 0, ',', '.'),
                    'difference_raw' => $item->settlement->difference_amount,
                    'trip_report' => $item->settlement->trip_report,
                    'status' => $item->settlement->status,
                    'expense_items' => $item->settlement->expenseItems,
                    'attachments' => $item->settlement->attachments,
                ] : null,
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'rejected_reason' => $item->rejected_reason,
                'attachments' => $item->attachments,
                'approvals' => $item->approvals,
                'status_histories' => $item->statusHistories,
                'created_at' => $item->created_at->translatedFormat('d F Y H:i'),
            ];
        } elseif ($type === 'lembur') {
            $item = \App\Models\OvertimeRequest::with(['user.division', 'approvals.approver', 'statusHistories.changedBy', 'claim'])
                ->findOrFail($id);
            $requestData = [
                'id' => $item->id,
                'type' => 'lembur',
                'type_label' => 'Rencana Lembur',
                'request_number' => $item->request_number,
                'applicant' => [
                    'name' => $item->user->name,
                    'nik' => $item->user->nik,
                    'division' => $item->user->division?->name ?? '-',
                    'position' => $item->user->position ?? '-',
                ],
                'details' => [
                    'Tanggal Lembur' => $item->date->translatedFormat('d F Y'),
                    'Waktu Rencana' => $item->start_time . ' - ' . $item->end_time,
                    'Target / Deskripsi Pekerjaan' => $item->task_description,
                ],
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'rejected_reason' => $item->rejected_reason,
                'approvals' => $item->approvals,
                'status_histories' => $item->statusHistories,
                'created_at' => $item->created_at->translatedFormat('d F Y H:i'),
                'has_claim' => $item->claim ? true : false,
            ];
        } elseif ($type === 'klaim-lembur') {
            $item = \App\Models\OvertimeClaim::with(['user.division', 'request', 'level2Approver', 'attachments', 'approvals.approver', 'statusHistories.changedBy', 'paidBy'])
                ->findOrFail($id);
            $requestData = [
                'id' => $item->id,
                'type' => 'klaim-lembur',
                'type_label' => 'Klaim Pencairan Lembur',
                'request_number' => $item->claim_number,
                'applicant' => [
                    'name' => $item->user->name,
                    'nik' => $item->user->nik,
                    'division' => $item->user->division?->name ?? '-',
                    'position' => $item->user->position ?? '-',
                ],
                'amount' => $item->amount,
                'details' => array_filter([
                    'No. Rencana Lembur' => $item->request->request_number,
                    'Tanggal Lembur' => $item->request->date->translatedFormat('d F Y'),
                    'Waktu Aktual' => $item->actual_start_time . ' - ' . $item->actual_end_time,
                    'Target / Deskripsi Pekerjaan' => $item->request->task_description,
                    'Manager Pilihan (Level 2)' => $item->level2Approver?->name,
                    'Nominal Klaim' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                    'Nomor Referensi Pembayaran' => $item->payment_reference,
                    'Waktu Pembayaran' => $item->paid_at ? $item->paid_at->translatedFormat('d F Y H:i') : null,
                    'Diproses Oleh' => $item->paidBy?->name,
                ]),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'rejected_reason' => $item->rejected_reason,
                'attachments' => $item->attachments,
                'approvals' => $item->approvals,
                'status_histories' => $item->statusHistories,
                'created_at' => $item->created_at->translatedFormat('d F Y H:i'),
            ];
        }

        $currentUser = request()->user();
        $canApprove = false;
        $pendingApprovalLevel = null;

        if ($currentUser && $requestData) {
            $pendingApproval = \App\Models\Approval::where('approvable_type', get_class($item))
                ->where('approvable_id', $item->id)
                ->where('status', 'pending')
                ->where('approver_id', $currentUser->id)
                ->first();

            if ($pendingApproval) {
                $canApprove = true;
                $pendingApprovalLevel = $pendingApproval->level;
            }
        }

        if ($requestData) {
            $requestData['can_approve'] = $canApprove;
            $requestData['pending_approval_level'] = $pendingApprovalLevel;
        }

        $cashAccounts = \App\Models\CashAccount::where('is_active', true)
            ->get()
            ->map(fn($acc) => [
                'id' => $acc->id,
                'name' => $acc->name,
                'code' => $acc->code,
                'type' => $acc->type,
                'type_label' => ($acc->type === 'bank') ? 'Rekening Bank' : 'Kas Tunai',
                'bank_name' => $acc->bank_name,
                'account_number' => $acc->account_number,
                'current_balance_formatted' => 'Rp ' . number_format($acc->current_balance, 0, ',', '.'),
            ]);

        return Inertia::render('RiwayatPengajuan/Show', [
            'requestData' => $requestData,
            'cashAccounts' => $cashAccounts,
        ]);
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $user = $request->user();

        $model = match($type) {
            'reimbursement' => ReimbursementRequest::findOrFail($id),
            'operasional' => OperationalRequest::findOrFail($id),
            'cuti' => LeaveRequest::findOrFail($id),
            'perjalanan-dinas' => \App\Models\BusinessTripRequest::findOrFail($id),
            'lembur' => \App\Models\OvertimeRequest::findOrFail($id),
            'klaim-lembur' => \App\Models\OvertimeClaim::findOrFail($id),
            default => abort(404),
        };

        // Check ownership or admin
        if ($model->user_id !== $user->id && !$user->hasRole('admin')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus pengajuan ini.');
        }

        // Only allow deletion if status is draft or submitted (not yet fully approved, paid, or completed)
        if (in_array($model->status->value, ['approved', 'paid', 'completed'])) {
            return back()->with('error', 'Pengajuan yang telah disetujui / dibayarkan tidak dapat dihapus.');
        }

        $requestNumber = $model->request_number;

        \Illuminate\Support\Facades\DB::transaction(function() use ($model) {
            // Delete attachments
            if ($model->attachments) {
                foreach ($model->attachments as $att) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($att->file_path);
                    $att->delete();
                }
            }

            // Delete approvals & status histories using relationships
            $model->approvals()->delete();
            $model->statusHistories()->delete();

            // Delete model
            $model->delete();
        });

        return redirect()->route('riwayat-pengajuan.index')->with('success', "Pengajuan {$requestNumber} berhasil dihapus.");
    }
}
