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
            ->orderBy('id')
            ->get()
            ->map(fn($acc) => [
                'id' => $acc->id,
                'name' => $acc->name,
                'code' => $acc->code,
                'type' => $acc->type ?? 'cash',
                'type_label' => ($acc->type === 'bank') ? 'Rekening Bank' : 'Kas Tunai',
                'bank_name' => $acc->bank_name,
                'account_number' => $acc->account_number,
                'current_balance' => (float)$acc->current_balance,
                'current_balance_formatted' => 'Rp ' . number_format($acc->current_balance, 0, ',', '.'),
                'pic_name' => $acc->picUser?->name ?? 'Admin Keuangan',
                'pic_user_id' => $acc->pic_user_id,
                'is_active' => (bool)$acc->is_active,
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

        $usersList = \App\Models\User::orderBy('name')->get(['id', 'name']);

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
            'usersList' => $usersList,
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

    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cash_accounts,code',
            'type' => 'required|in:cash,bank',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'current_balance' => 'required|numeric|min:0',
            'pic_user_id' => 'nullable|exists:users,id',
        ]);

        $account = CashAccount::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'type' => $validated['type'],
            'bank_name' => $validated['type'] === 'bank' ? $validated['bank_name'] : null,
            'account_number' => $validated['type'] === 'bank' ? $validated['account_number'] : null,
            'current_balance' => $validated['current_balance'],
            'pic_user_id' => $validated['pic_user_id'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', "Akun Kas '{$account->name}' berhasil ditambahkan!");
    }

    public function updateAccount(Request $request, int $id): RedirectResponse
    {
        $account = CashAccount::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cash_accounts,code,' . $id,
            'type' => 'required|in:cash,bank',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'current_balance' => 'required|numeric|min:0',
            'pic_user_id' => 'nullable|exists:users,id',
            'is_active' => 'required|boolean',
        ]);

        $account->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'type' => $validated['type'],
            'bank_name' => $validated['type'] === 'bank' ? $validated['bank_name'] : null,
            'account_number' => $validated['type'] === 'bank' ? $validated['account_number'] : null,
            'current_balance' => $validated['current_balance'],
            'pic_user_id' => $validated['pic_user_id'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->back()->with('success', "Data Akun Kas '{$account->name}' berhasil diperbarui!");
    }
}
