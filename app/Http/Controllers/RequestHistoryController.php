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
                    'date' => "{$item->start_date->format('d M')} - {$item->end_date->format('d M Y')} ({$item->total_days} hari)",
                    'amount' => null,
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
                'details' => [
                    'Jenis Pengeluaran' => $item->expenseType?->name,
                    'Tanggal Pengeluaran' => $item->expense_date->translatedFormat('d F Y'),
                    'Nominal' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                    'Keterangan' => $item->description,
                    'Nomor Referensi Pembayaran' => $item->payment_reference ?? '-',
                    'Waktu Pembayaran' => $item->paid_at ? $item->paid_at->translatedFormat('d F Y H:i') : '-',
                    'Diproses Oleh' => $item->paidBy?->name ?? '-',
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
                'details' => [
                    'Jenis Kegiatan' => $item->activityType?->name,
                    'Nama Kegiatan' => $item->activity_name,
                    'Tanggal Kegiatan' => $item->activity_date->translatedFormat('d F Y'),
                    'Jumlah Peserta' => $item->participant_count . ' Orang',
                    'Estimasi Biaya' => 'Rp ' . number_format($item->estimated_cost, 0, ',', '.'),
                    'Lokasi' => $item->location,
                    'Tujuan / Keterangan' => $item->purpose,
                    'Nomor Referensi Pembayaran' => $item->payment_reference ?? '-',
                    'Waktu Pembayaran' => $item->paid_at ? $item->paid_at->translatedFormat('d F Y H:i') : '-',
                    'Diproses Oleh' => $item->paidBy?->name ?? '-',
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
        }

        $currentUser = request()->user();
        $canApprove = false;
        $pendingApprovalLevel = null;

        if ($currentUser && $requestData && $currentUser->id !== $item->user_id) {
            if ($item->status->value === 'submitted') {
                if ($item->user->manager_id === $currentUser->id || $currentUser->hasRole('admin') || $currentUser->hasRole('manager')) {
                    $canApprove = true;
                    $pendingApprovalLevel = 1;
                }
            } elseif ($item->status->value === 'level_1_approved') {
                if ($currentUser->hasRole('hrd_finance') || $currentUser->hasRole('admin')) {
                    $canApprove = true;
                    $pendingApprovalLevel = 2;
                }
            }
        }

        if ($requestData) {
            $requestData['can_approve'] = $canApprove;
            $requestData['pending_approval_level'] = $pendingApprovalLevel;
        }

        return Inertia::render('RiwayatPengajuan/Show', [
            'requestData' => $requestData,
        ]);
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $user = $request->user();

        $model = match($type) {
            'reimbursement' => ReimbursementRequest::findOrFail($id),
            'operasional' => OperationalRequest::findOrFail($id),
            'cuti' => LeaveRequest::findOrFail($id),
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
