<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ArchiveAuditTrailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:archive {--months=12 : Usia data dalam bulan sebelum diarsipkan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Arsipkan log jejak audit yang berusia lebih dari batas bulan ke file penyimpanan dingin (cold storage) dan bersihkan tabel PostgreSQL.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = (int) $this->option('months');
        $cutoffDate = Carbon::now()->subMonths($months);

        $this->info("Memeriksa log audit trail sebelum tanggal: {$cutoffDate->toDateTimeString()} ({$months} bulan lalu)");

        $oldLogs = DB::table('audit_trails')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = $oldLogs->count();
        if ($count === 0) {
            $this->info("Tidak ada log audit yang melebihi batas usia {$months} bulan. Tabel operasional tetap ramping.");
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$count} entri log usang. Menyiapkan arsip berkas json/csv...");

        $archiveFileName = 'archives/audit-trails-' . Carbon::now()->format('Y-m-d-His') . '.json';
        $jsonData = json_encode($oldLogs, JSON_PRETTY_PRINT);

        // Simpan ke storage lokal / S3
        Storage::disk('local')->put($archiveFileName, $jsonData);
        $this->info("Data berhasil diarsipkan secara aman di: {$archiveFileName}");

        // Hapus dari tabel PostgreSQL utama
        $deleted = DB::table('audit_trails')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("Berhasil membersihkan {$deleted} baris data dari tabel audit_trails.");

        return Command::SUCCESS;
    }
}

