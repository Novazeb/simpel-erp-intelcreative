<?php

namespace App\Domains\Procurement\Services;

use App\Domains\Finance\Models\ChartOfAccount;
use App\Domains\Finance\Models\JournalEntry;
use App\Domains\Finance\Models\JournalItem;
use App\Domains\Procurement\Models\AssetDepreciation;
use App\Domains\Procurement\Models\AssetItem;
use Illuminate\Support\Facades\DB;

class AssetDepreciationService
{
    /**
     * Menjalankan Penyusutan Garis Lurus (Straight-Line) Bulanan & Auto-Journal
     */
    public function processMonthlyDepreciation(): int
    {
        $processedCount = 0;
        $activeAssets = AssetItem::where('status', 'IN_USE')
            ->where('current_book_value', '>', DB::raw('salvage_value'))
            ->get();

        foreach ($activeAssets as $asset) {
            DB::transaction(function () use ($asset, &$processedCount) {
                // Formula Straight-Line Monthly Depreciation = (Cost - Salvage) / (Life_Years * 12)
                $monthlyAmount = ($asset->purchase_cost - $asset->salvage_value) / ($asset->useful_life_years * 12);
                $monthlyAmount = min($monthlyAmount, (float) ($asset->current_book_value - $asset->salvage_value));

                if ($monthlyAmount <= 0) {
                    return;
                }

                $newBookValue = (float) $asset->current_book_value - $monthlyAmount;

                // 1. Catat Jurnal Akuntansi Beban Penyusutan
                $depreciationExpenseAcc = ChartOfAccount::firstOrCreate(
                    ['code' => '5-3001'],
                    ['name' => 'Beban Penyusutan Aset', 'type' => 'EXPENSE']
                );
                $accumDepreciationAcc = ChartOfAccount::firstOrCreate(
                    ['code' => '1-2002'],
                    ['name' => 'Akumulasi Penyusutan Aset', 'type' => 'ASSET']
                );

                $journal = JournalEntry::create([
                    'entry_number' => 'JRN-DEP-'.date('Ym').'-'.strtoupper(substr(str_replace('-', '', (string) $asset->id), 0, 4)),
                    'reference_type' => 'ASSET_DEPRECIATION',
                    'reference_id' => $asset->id,
                    'transaction_date' => now()->toDateString(),
                    'description' => "Penyusutan Bulanan Aset: {$asset->name} ({$asset->asset_code})",
                ]);

                // Debit Beban Penyusutan
                JournalItem::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $depreciationExpenseAcc->id,
                    'debit' => $monthlyAmount,
                    'credit' => 0.00,
                ]);

                // Kredit Akumulasi Penyusutan
                JournalItem::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $accumDepreciationAcc->id,
                    'debit' => 0.00,
                    'credit' => $monthlyAmount,
                ]);

                // 2. Rekam Log Depresiasi Aset
                AssetDepreciation::create([
                    'asset_item_id' => $asset->id,
                    'depreciation_date' => now()->toDateString(),
                    'depreciation_amount' => $monthlyAmount,
                    'accumulated_depreciation' => (float) ($asset->purchase_cost - $newBookValue),
                    'book_value_after' => $newBookValue,
                    'journal_entry_id' => $journal->id,
                ]);

                // 3. Update Book Value Aset
                $asset->update(['current_book_value' => $newBookValue]);
                $processedCount++;
            });
        }

        return $processedCount;
    }
}
