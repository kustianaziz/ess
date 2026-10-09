<?php

use App\Models\ExpenseType;
use App\Models\ReimbursementRequest;
use App\Models\Attachment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan tipe pengeluaran Langganan & Layanan AI
        $aiType = ExpenseType::firstOrCreate(
            ['name' => 'Langganan & Layanan AI'],
            ['is_active' => true]
        );

        // 2. Pindai dan pindahkan otomatis transaksi reimbursement di database yang mengandung kata kunci AI (gemini, agy, antigravity, claude, chat gpt, openai, cursor, copilot, dll)
        $keywords = [
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

        ReimbursementRequest::where('expense_type_id', '!=', $aiType->id)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('description', 'like', "%{$kw}%");
                }
            })
            ->update(['expense_type_id' => $aiType->id]);

        // 3. Pindahkan dan sesuaikan transaksi reimbursement testing lokal (jika ada) ke kategori AI
        // Transaksi 1: RMB-202609-0430 (Kustian - ChatGPT Plus & Claude Pro)
        $reimb5 = ReimbursementRequest::where('request_number', 'RMB-202609-0430')->first();
        if ($reimb5) {
            $reimb5->update([
                'expense_type_id' => $aiType->id,
                'description' => 'Langganan bulanan AI Tools (ChatGPT Plus & Claude Pro) untuk peningkatan produktivitas kerja',
            ]);

            // Sesuaikan nama attachment agar informatif
            $att = $reimb5->attachments()->first();
            if ($att) {
                $att->update([
                    'file_name' => 'Invoice_Langganan_ChatGPT_Plus_Claude_Pro.jpg',
                ]);
            }
        }

        // Transaksi 2: RMB-202609-0131 (Kustian - Cursor Pro & GitHub Copilot)
        $reimb4 = ReimbursementRequest::where('request_number', 'RMB-202609-0131')->first();
        if ($reimb4) {
            $reimb4->update([
                'expense_type_id' => $aiType->id,
                'description' => 'Langganan Cursor Pro & GitHub Copilot untuk efisiensi coding dan otomasi tugas teknis',
            ]);

            if (!$reimb4->attachments()->exists()) {
                Attachment::create([
                    'attachable_type' => ReimbursementRequest::class,
                    'attachable_id' => $reimb4->id,
                    'file_name' => 'Struk_Tagihan_Cursor_AI_Subscription.png',
                    'file_path' => 'attachments/reimbursements/iKOS4CURbzQ9gitsidFbDM3tCVQTpFKVqN9MukL1.png',
                    'file_type' => 'image/png',
                    'file_size' => 95400,
                    'uploaded_by' => $reimb4->user_id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $transportType = ExpenseType::where('name', 'Transportasi')->first();
        if ($transportType) {
            ReimbursementRequest::whereIn('request_number', ['RMB-202609-0430', 'RMB-202609-0131'])
                ->update(['expense_type_id' => $transportType->id]);
        }
    }
};
