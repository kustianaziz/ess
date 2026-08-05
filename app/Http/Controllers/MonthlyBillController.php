<?php

namespace App\Http\Controllers;

use App\Actions\CashOperational\RecordCashTransactionAction;
use App\Actions\Shared\GenerateRequestNumberAction;
use App\Models\CashAccount;
use App\Models\MonthlyBillPayment;
use App\Models\MonthlyBillType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyBillController extends Controller
{
    public function index(
        Request $request,
        GenerateRequestNumberAction $generateRequestNumber
    ): Response {
        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        // 1. Auto-generate payments for active bill types for selected period if not exist
        $activeBillTypes = MonthlyBillType::where('is_active', true)->get();

        foreach ($activeBillTypes as $billType) {
            $existing = MonthlyBillPayment::where('bill_type_id', $billType->id)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            if (!$existing) {
                $dueDate = sprintf('%04d-%02d-%02d', $year, $month, min($billType->billing_day ?? 10, 28));
                MonthlyBillPayment::create([
                    'payment_number' => $generateRequestNumber->execute('TB', 'monthly_bill_payments', 'payment_number'),
                    'bill_type_id' => $billType->id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'bill_amount' => $billType->default_amount ?? 0,
                    'due_date' => $dueDate,
                    'status' => 'unpaid',
                ]);
            }
        }

        // 2. Query payments for current selected period
        $payments = MonthlyBillPayment::with(['billType.cashAccount', 'paidBy:id,name'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->latest('due_date')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'payment_number' => $p->payment_number,
                'bill_type_name' => $p->billType?->name ?? '-',
                'vendor_name' => $p->billType?->vendor_name ?? '-',
                'cash_account_id' => $p->billType?->cash_account_id,
                'cash_account_name' => $p->billType?->cashAccount?->name ?? 'Kas Operasional Pusat',
                'bill_amount' => (float)$p->bill_amount,
                'bill_amount_formatted' => 'Rp ' . number_format($p->bill_amount, 0, ',', '.'),
                'due_date' => $p->due_date->translatedFormat('d M Y'),
                'payment_date' => $p->payment_date ? $p->payment_date->translatedFormat('d M Y') : '-',
                'status' => $p->status,
                'payment_reference' => $p->payment_reference,
                'notes' => $p->notes,
                'paid_by_name' => $p->paidBy?->name ?? '-',
            ]);

        $totalBillAmount = $payments->sum('bill_amount');
        $totalPaidAmount = $payments->where('status', 'paid')->sum('bill_amount');
        $totalUnpaidAmount = $payments->where('status', 'unpaid')->sum('bill_amount');

        $cashAccounts = CashAccount::where('is_active', true)->get(['id', 'name', 'code']);

        return Inertia::render('Keuangan/TagihanBulanan/Index', [
            'payments' => $payments,
            'summary' => [
                'total_bill' => (float)$totalBillAmount,
                'total_bill_formatted' => 'Rp ' . number_format($totalBillAmount, 0, ',', '.'),
                'total_paid' => (float)$totalPaidAmount,
                'total_paid_formatted' => 'Rp ' . number_format($totalPaidAmount, 0, ',', '.'),
                'total_unpaid' => (float)$totalUnpaidAmount,
                'total_unpaid_formatted' => 'Rp ' . number_format($totalUnpaidAmount, 0, ',', '.'),
            ],
            'month' => $month,
            'year' => $year,
            'cashAccounts' => $cashAccounts,
        ]);
    }

    public function pay(
        Request $request,
        int $id,
        RecordCashTransactionAction $recordCashTransaction
    ): RedirectResponse {
        $payment = MonthlyBillPayment::with('billType')->findOrFail($id);

        $validated = $request->validate([
            'bill_amount' => 'required|numeric|min:1',
            'payment_reference' => 'required|string|max:100',
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($payment, $validated, $user, $recordCashTransaction) {
            $payment->update([
                'bill_amount' => $validated['bill_amount'],
                'payment_reference' => $validated['payment_reference'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'paid',
                'paid_by' => $user->id,
            ]);

            // Record cash out transaction
            $recordCashTransaction->execute(
                $validated['cash_account_id'],
                'out',
                'pembayaran_bulanan',
                $validated['bill_amount'],
                "Pembayaran Tagihan Bulanan: {$payment->billType->name} ({$payment->payment_number})",
                $user->id,
                $payment,
                $validated['payment_date']
            );
        });

        return redirect()->back()->with(
            'success',
            "Pembayaran tagihan {$payment->billType->name} ({$payment->payment_number}) berhasil diproses!"
        );
    }

    public function storeBillType(
        Request $request,
        GenerateRequestNumberAction $generateRequestNumber
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
            'default_amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'cash_account_id' => 'required|exists:cash_accounts,id',
        ]);

        $dueDate = $validated['due_date'];
        $time = strtotime($dueDate);
        $billingDay = (int) date('j', $time);
        $month = (int) date('n', $time);
        $year = (int) date('Y', $time);

        $billType = MonthlyBillType::create([
            'name' => $validated['name'],
            'vendor_name' => $validated['vendor_name'] ?? null,
            'default_amount' => $validated['default_amount'],
            'billing_day' => min($billingDay, 28),
            'cash_account_id' => $validated['cash_account_id'],
            'is_active' => true,
        ]);

        // Auto-generate payment for specified month/year
        MonthlyBillPayment::create([
            'payment_number' => $generateRequestNumber->execute('TB', 'monthly_bill_payments', 'payment_number'),
            'bill_type_id' => $billType->id,
            'period_month' => $month,
            'period_year' => $year,
            'bill_amount' => $billType->default_amount,
            'due_date' => $dueDate,
            'status' => 'unpaid',
        ]);

        return redirect()->back()->with(
            'success',
            "Jenis tagihan '{$billType->name}' berhasil ditambahkan untuk periode " . date('F Y', $time) . "!"
        );
    }
}
