<?php

namespace App\Http\Controllers;

use App\Actions\CashOperational\RecordCashTransactionAction;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashOperationalController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $request->user();

        // 1. Cash Accounts List
        $cashAccounts = CashAccount::with('picUser:id,name')
            ->where('is_active', true)
            ->get()
            ->map(fn($acc) => [
                'id' => $acc->id,
                'name' => $acc->name,
                'code' => $acc->code,
                'current_balance' => (float)$acc->current_balance,
                'current_balance_formatted' => 'Rp ' . number_format($acc->current_balance, 0, ',', '.'),
                'pic_name' => $acc->picUser?->name ?? 'Admin Keuangan',
            ]);

        $totalBalance = $cashAccounts->sum('current_balance');

        // 2. Monthly Stats
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $totalCashIn = CashTransaction::where('type', 'in')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $totalCashOut = CashTransaction::where('type', 'out')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // 3. Transactions List
        $query = CashTransaction::with(['cashAccount', 'createdBy:id,name'])
            ->latest('transaction_date')
            ->latest('id');

        if ($request->filled('cash_account_id')) {
            $query->where('cash_account_id', $request->input('cash_account_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        $transactions->getCollection()->transform(fn($tx) => [
            'id' => $tx->id,
            'transaction_number' => $tx->transaction_number,
            'cash_account_name' => $tx->cashAccount?->name ?? '-',
            'type' => $tx->type,
            'category' => $tx->category,
            'category_label' => match($tx->category) {
                'perjalanan_dinas' => 'Perjalanan Dinas',
                'reimburse' => 'Reimbursement',
                'pembayaran_bulanan' => 'Tagihan Bulanan',
                'operasional_lain' => 'Operasional Lain',
                'setoran_kas' => 'Setoran Kas',
                default => 'Lainnya',
            },
            'amount' => (float)$tx->amount,
            'amount_formatted' => ($tx->type === 'in' ? '+ Rp ' : '- Rp ') . number_format($tx->amount, 0, ',', '.'),
            'description' => $tx->description,
            'transaction_date' => $tx->transaction_date->translatedFormat('d M Y'),
            'created_by' => $tx->createdBy?->name ?? 'System',
        ]);

        return Inertia::render('Keuangan/KasOperasional/Dashboard', [
            'cashAccounts' => $cashAccounts,
            'summary' => [
                'total_balance' => (float)$totalBalance,
                'total_balance_formatted' => 'Rp ' . number_format($totalBalance, 0, ',', '.'),
                'total_cash_in' => (float)$totalCashIn,
                'total_cash_in_formatted' => 'Rp ' . number_format($totalCashIn, 0, ',', '.'),
                'total_cash_out' => (float)$totalCashOut,
                'total_cash_out_formatted' => 'Rp ' . number_format($totalCashOut, 0, ',', '.'),
            ],
            'transactions' => $transactions,
            'filters' => $request->only(['cash_account_id', 'type', 'category', 'search']),
        ]);
    }

    public function storeTransaction(
        Request $request,
        RecordCashTransactionAction $recordCashTransaction
    ): RedirectResponse {
        $validated = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'type' => 'required|in:in,out',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'transaction_date' => 'required|date',
        ]);

        $user = $request->user();

        $tx = $recordCashTransaction->execute(
            $validated['cash_account_id'],
            $validated['type'],
            $validated['category'],
            $validated['amount'],
            $validated['description'],
            $user->id,
            null,
            $validated['transaction_date']
        );

        $typeLabel = $validated['type'] === 'in' ? 'Kas Masuk' : 'Kas Keluar';

        return redirect()->route('keuangan.kas-operasional.dashboard')->with(
            'success',
            "Transaksi {$typeLabel} ({$tx->transaction_number}) senilai Rp " . number_format($tx->amount, 0, ',', '.') . " berhasil dicatat!"
        );
    }
}
