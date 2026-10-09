<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseType;
use App\Models\ReimbursementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseTypeController extends Controller
{
    private array $aiKeywords = [
        'gemini',
        'agy',
        'antigravity',
        'claude',
        'chatgpt',
        'chat gpt',
        'gpt',
        'openai',
        'cursor',
        'copilot',
        'midjourney',
        'anthropic',
        'perplexity',
        'deepseek',
        'v0.dev',
        'ai tools',
        'ai tool',
        'ai subscription',
        'langganan ai',
    ];

    public function index(): Response
    {
        $types = ExpenseType::withCount('reimbursementRequests')->latest()->get();

        $aiType = $types->firstWhere('name', 'Langganan & Layanan AI');
        $aiCount = $aiType ? ($aiType->reimbursement_requests_count ?? 0) : 0;

        $unconvertedAiCount = 0;
        if ($aiType) {
            $unconvertedAiCount = ReimbursementRequest::where('expense_type_id', '!=', $aiType->id)
                ->where(function ($q) {
                    foreach ($this->aiKeywords as $kw) {
                        $q->orWhere('description', 'like', "%{$kw}%");
                    }
                })->count();
        }

        return Inertia::render('Admin/ExpenseTypes/Index', [
            'expenseTypes' => $types,
            'aiStats' => [
                'ai_type_id' => $aiType?->id,
                'converted_count' => $aiCount,
                'unconverted_count' => $unconvertedAiCount,
            ],
        ]);
    }

    public function categorizeAi(Request $request): RedirectResponse
    {
        $aiType = ExpenseType::firstOrCreate(
            ['name' => 'Langganan & Layanan AI'],
            ['is_active' => true]
        );

        $candidates = ReimbursementRequest::with(['user', 'expenseType'])
            ->where('expense_type_id', '!=', $aiType->id)
            ->where(function ($q) {
                foreach ($this->aiKeywords as $kw) {
                    $q->orWhere('description', 'like', "%{$kw}%");
                }
            })
            ->get();

        $count = 0;
        foreach ($candidates as $cand) {
            $cand->update(['expense_type_id' => $aiType->id]);
            $count++;
        }

        if ($count > 0) {
            return redirect()->route('admin.expense-types.index')->with(
                'success',
                "Berhasil! Sebanyak {$count} transaksi reimbursement terkait AI (Gemini, AGY, Claude, ChatGPT, Cursor, dll) telah otomatis dipindahkan ke kategori '{$aiType->name}'."
            );
        }

        return redirect()->route('admin.expense-types.index')->with(
            'success',
            "Seluruh transaksi reimbursement terkait AI sudah berada dalam kategori '{$aiType->name}'. Tidak ada transaksi baru yang perlu dipindahkan."
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_types,name',
            'is_active' => 'required|boolean',
        ]);

        ExpenseType::create($validated);

        return back()->with('success', 'Jenis pengeluaran berhasil ditambahkan!');
    }

    public function update(Request $request, ExpenseType $expenseType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:expense_types,name,{$expenseType->id}",
            'is_active' => 'required|boolean',
        ]);

        $expenseType->update($validated);

        return back()->with('success', 'Jenis pengeluaran berhasil diperbarui!');
    }

    public function destroy(ExpenseType $expenseType): RedirectResponse
    {
        $expenseType->delete();

        return back()->with('success', 'Jenis pengeluaran berhasil dihapus!');
    }
}
