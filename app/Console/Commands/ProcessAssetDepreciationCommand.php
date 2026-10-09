<?php

namespace App\Console\Commands;

use App\Domains\Procurement\Services\AssetDepreciationService;
use Illuminate\Console\Command;

class ProcessAssetDepreciationCommand extends Command
{
    protected $signature = 'asset:process-depreciation';

    protected $description = 'Menjalankan penyusutan aset garis lurus bulanan dan menerbitkan auto-journal keuangan';

    public function handle(AssetDepreciationService $service): int
    {
        $count = $service->processMonthlyDepreciation();
        $this->info("Penyusutan selesai. Total {$count} aset telah disusutkan dan dijurnal.");

        return Command::SUCCESS;
    }
}
