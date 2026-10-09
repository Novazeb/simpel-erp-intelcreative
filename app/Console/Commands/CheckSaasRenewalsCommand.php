<?php

namespace App\Console\Commands;

use App\Domains\Finance\Models\SaasSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSaasRenewalsCommand extends Command
{
    protected $signature = 'saas:check-renewals';

    protected $description = 'Memeriksa subskripsi SaaS yang jatuh tempo dalam 7 hari kedepan dan memicu peringatan renewal';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();
        $targetDate = Carbon::today()->addDays(7)->toDateString();

        $expiringSubscriptions = SaasSubscription::where('status', 'ACTIVE')
            ->whereBetween('next_billing_date', [$today, $targetDate])
            ->get();

        $alertCount = 0;
        foreach ($expiringSubscriptions as $sub) {
            $daysLeft = Carbon::today()->diffInDays(Carbon::parse($sub->next_billing_date), false);
            $message = "PERINGATAN SAAS: Subskripsi {$sub->software_name} ({$sub->vendor_name}) akan jatuh tempo dalam {$daysLeft} hari pada {$sub->next_billing_date}. Biaya: Rp ".number_format((float) $sub->cost_per_cycle, 2, ',', '.');

            $this->warn($message);
            Log::warning($message, [
                'saas_id' => $sub->id,
                'software' => $sub->software_name,
                'next_billing_date' => $sub->next_billing_date,
            ]);
            $alertCount++;
        }

        $this->info("Pemeriksaan selesai. Ditemukan {$alertCount} subskripsi SaaS yang mendekati jatuh tempo.");

        return Command::SUCCESS;
    }
}
