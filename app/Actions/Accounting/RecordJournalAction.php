<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Coa;
use App\Actions\Shared\GenerateRequestNumberAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RecordJournalAction
{
    public function __construct(
        protected GenerateRequestNumberAction $generateRequestNumber
    ) {}

    public function execute(
        string $date,
        string $description,
        array $items, // array of ['coa_id' => x, 'debit' => y, 'credit' => z, 'description' => a]
        ?Model $referenceModel = null,
        ?int $createdById = null
    ): JournalEntry {
        return DB::transaction(function () use ($date, $description, $items, $referenceModel, $createdById) {
            $journalNumber = $this->generateRequestNumber->execute('JE', 'journal_entries', 'journal_number');

            $journalEntry = JournalEntry::create([
                'journal_number' => $journalNumber,
                'date' => $date,
                'description' => $description,
                'reference_type' => $referenceModel ? get_class($referenceModel) : null,
                'reference_id' => $referenceModel ? $referenceModel->id : null,
                'status' => 'posted',
                'created_by' => $createdById ?? Auth::id() ?? 1,
            ]);

            foreach ($items as $item) {
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'coa_id' => $item['coa_id'],
                    'debit' => $item['debit'] ?? 0,
                    'credit' => $item['credit'] ?? 0,
                    'description' => $item['description'] ?? $description,
                ]);
            }

            return $journalEntry;
        });
    }
}
