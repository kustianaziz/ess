<?php

namespace App\Console\Commands;

use App\Models\ExpenseType;
use App\Models\ReimbursementRequest;
use Illuminate\Console\Command;

class CategorizeAiReimbursementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reimbursement:categorize-ai {--dry-run : Hanya tampilkan pratinjau data tanpa menyimpan perubahan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindai dan konversikan transaksi reimbursement lama yang menggunakan tools AI ke kategori Langganan & Layanan AI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=====================================================');
        $this->info('  MIGRASI / KONVERSI TRANSAKSI REIMBURSEMENT TOOLS AI');
        $this->info('=====================================================');

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('[DRY-RUN MODE AKTIF] Tidak ada data yang akan diubah ke database.');
        }

        // 1. Pastikan kategori Langganan & Layanan AI tersedia
        $aiType = ExpenseType::firstOrCreate(
            ['name' => 'Langganan & Layanan AI'],
            ['is_active' => true]
        );

        $this->line("Target Kategori: <comment>{$aiType->name}</comment> (ID: {$aiType->id})");

        // 2. Daftar kata kunci AI yang diajukan tim
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

        // 3. Cari pengajuan yang mengandung kata kunci AI tapi belum masuk kategori Langganan & Layanan AI
        $query = ReimbursementRequest::with(['user', 'expenseType'])
            ->where('expense_type_id', '!=', $aiType->id)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('description', 'like', "%{$kw}%");
                }
            });

        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            $this->info('Tidak ditemukan transaksi reimburse lama terkait AI yang belum dikonversi.');
            return Command::SUCCESS;
        }

        $this->line("Ditemukan <info>{$candidates->count()}</info> transaksi reimbursement terkait AI:");

        $tableData = $candidates->map(function ($req) {
            return [
                'ID' => $req->id,
                'No. Pengajuan' => $req->request_number,
                'Pemohon' => $req->user?->name ?? '-',
                'Kategori Lama' => $req->expenseType?->name ?? '-',
                'Nominal' => 'Rp ' . number_format($req->amount, 0, ',', '.'),
                'Deskripsi' => \Illuminate\Support\Str::limit($req->description, 50),
                'Status' => $req->status->value,
            ];
        });

        $this->table(
            ['ID', 'No. Pengajuan', 'Pemohon', 'Kategori Lama', 'Nominal', 'Deskripsi', 'Status'],
            $tableData
        );

        if ($isDryRun) {
            $this->warn('Pratinjau selesai. Jalankan "php artisan reimbursement:categorize-ai" untuk mengeksekusi konversi.');
            return Command::SUCCESS;
        }

        if (!$this->confirm('Apakah Anda yakin ingin memindahkan transaksi di atas ke kategori Langganan & Layanan AI?', true)) {
            $this->warn('Operasi dibatalkan.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($candidates as $req) {
            $req->update(['expense_type_id' => $aiType->id]);
            $count++;
        }

        $this->info("✓ Berhasil memindahkan {$count} transaksi reimbursement ke kategori '{$aiType->name}'!");

        return Command::SUCCESS;
    }
}
