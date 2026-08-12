<?php

namespace App\Services;

use App\Models\Coa;
use App\Models\JournalItem;

class FinancialReportService
{
    public function getCoaTreeWithBalances($startDate, $endDate, $asOfDate = null, $levelLimit = null, $showZero = true, $injectedBalances = [])
    {
        $allCoas = Coa::orderBy('code')->get();
        $grouped = $allCoas->groupBy('parent_id');
        
        $flatList = [];
        $tree = [];

        $build = function($parentId, $level) use (&$build, &$grouped, &$flatList) {
            if (!isset($grouped[$parentId])) return [];
            
            $nodes = [];
            foreach ($grouped[$parentId] as $coa) {
                $coa->level = $level;
                $flatList[] = $coa;
                $coa->children = $build($coa->id, $level + 1);
                $nodes[] = $coa;
            }
            return $nodes;
        };

        $tree = $build(null, 1);
        $maxLevel = count($flatList) > 0 ? collect($flatList)->max('level') : 1;

        // Calculate raw balances for detail COAs using native SQL grouping and aggregation for high performance
        $balances = [];
        
        $balQuery = JournalItem::selectRaw('coa_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereHas('journalEntry', function($q) use ($asOfDate, $startDate, $endDate) {
                $q->where('status', '!=', 'void');
                if ($asOfDate) {
                    $q->where('date', '<=', $asOfDate);
                } else {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }
            })
            ->groupBy('coa_id');

        $balancesList = $balQuery->get();

        foreach ($balancesList as $b) {
            $balances[$b->coa_id] = [
                'debit' => (float)$b->total_debit,
                'credit' => (float)$b->total_credit
            ];
        }

        // Inject manual/dynamic balances (e.g. Laba Ditahan, Laba Tahun Berjalan)
        foreach ($injectedBalances as $coaId => $balAmount) {
            if ($coaId) {
                if (!isset($balances[$coaId])) {
                    $balances[$coaId] = ['debit' => 0, 'credit' => 0];
                }
                if ($balAmount >= 0) {
                    $balances[$coaId]['credit'] += $balAmount;
                } else {
                    $balances[$coaId]['debit'] += abs($balAmount);
                }
            }
        }

        // Rollup balances
        $rollup = function($nodes) use (&$rollup, $balances) {
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($nodes as $node) {
                if ($node->is_header) {
                    $childBals = $rollup($node->children);
                    $node->raw_debit = $childBals['debit'];
                    $node->raw_credit = $childBals['credit'];
                } else {
                    $node->raw_debit = $balances[$node->id]['debit'] ?? 0;
                    $node->raw_credit = $balances[$node->id]['credit'] ?? 0;
                }
                
                // Calculate net balance based on normal balance
                if (in_array(strtolower($node->normal_balance), ['credit', 'kredit'])) {
                    $node->balance = $node->raw_credit - $node->raw_debit;
                } else {
                    $node->balance = $node->raw_debit - $node->raw_credit;
                }

                $totalDebit += $node->raw_debit;
                $totalCredit += $node->raw_credit;
            }
            return ['debit' => $totalDebit, 'credit' => $totalCredit];
        };

        $rollup($tree);

        // Filter by level and zero
        $filteredFlatList = collect($flatList)->filter(function($coa) use ($levelLimit, $showZero) {
            if ($levelLimit && $coa->level > $levelLimit) {
                return false;
            }
            if (!$showZero && $coa->balance == 0 && $coa->raw_debit == 0 && $coa->raw_credit == 0) {
                return false;
            }
            return true;
        })->values();

        $visibleIds = $filteredFlatList->pluck('id')->toArray();

        $filteredFlatList = $filteredFlatList->map(function($coa) use ($levelLimit, $visibleIds) {
            // Check if this COA has any visible children
            $hasVisibleChildren = false;
            if ($coa->children) {
                foreach ($coa->children as $child) {
                    if (in_array($child->id, $visibleIds)) {
                        $hasVisibleChildren = true;
                        break;
                    }
                }
            }
            
            // If it has no visible children, treat it as a detail account so its balance is shown
            if (!$hasVisibleChildren) {
                $coa->is_header = false;
            }
            return $coa;
        });

        return [
            'tree' => $tree,
            'flat' => $filteredFlatList,
            'maxLevel' => $maxLevel,
        ];
    }
}
