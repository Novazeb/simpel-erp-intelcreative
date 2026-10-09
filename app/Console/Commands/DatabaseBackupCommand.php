<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--encrypt : Enkripsi berkas cadangan dengan enkripsi AES-256}
                            {--disk=local : Media penyimpanan tujuan cadangan (local/s3/r2)}
                            {--clean-older-days=30 : Hapus cadangan usang yang melebihi jumlah hari ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lakukan pencadangan basis data otomatis, enkripsi file cadangan, dan rotasi retensi penyimpanan.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = $this->option('disk');
        $encrypt = (bool) $this->option('encrypt');
        $cleanDays = (int) $this->option('clean-older-days');
        $timestamp = Carbon::now()->format('Y-m-d-His');
        $connection = config('database.default');

        $this->info("Memulai pencadangan basis data [{$connection}] ke disk [{$disk}]...");

        $backupData = null;
        $extension = 'sql';

        if ($connection === 'sqlite' && file_exists(config('database.connections.sqlite.database', ''))) {
            $dbPath = config('database.connections.sqlite.database');
            $backupData = file_get_contents($dbPath);
            $extension = 'sqlite';
        } else {
            // Untuk PostgreSQL / MySQL / In-Memory SQLite, lakukan export struktur dan data tabel
            $tables = DB::getSchemaBuilder()->getTableListing();
            $dumpContent = "-- ERP PT INTEL CREATIVE DATABASE DUMP\n";
            $dumpContent .= '-- Generated at: '.Carbon::now()->toIso8601String()."\n\n";

            foreach ($tables as $table) {
                // Remove prefix if present (e.g. main.users -> users)
                $tableName = str_contains($table, '.') ? explode('.', $table)[1] : $table;
                try {
                    $rows = DB::table($tableName)->get();
                    $dumpContent .= "-- Table: {$tableName} (".count($rows)." rows)\n";
                    $dumpContent .= json_encode($rows, JSON_PRETTY_PRINT)."\n\n";
                } catch (\Throwable $e) {
                    continue;
                }
            }
            $backupData = $dumpContent;
            $extension = 'json.dump';
        }

        if ($encrypt) {
            $backupData = Crypt::encrypt($backupData);
            $extension .= '.enc';
        }

        $fileName = "backups/db-backup-{$timestamp}.{$extension}";

        try {
            Storage::disk($disk)->put($fileName, $backupData);
            $fileSizeKb = round(strlen($backupData) / 1024, 2);

            $this->info("Cadangan berhasil dibuat: {$fileName} ({$fileSizeKb} KB)");
            if ($encrypt) {
                $this->info('Enkripsi aktif: Berkas terenkripsi dengan aman (AES-256).');
            }

            // Catat ke log audit bila tabel tersedia
            try {
                if (Schema::hasTable('audit_trails')) {
                    DB::table('audit_trails')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => null,
                        'event' => 'DATABASE_BACKUP_CREATED',
                        'auditable_type' => 'System\\Backup',
                        'auditable_id' => $fileName,
                        'old_values' => null,
                        'new_values' => json_encode([
                            'file' => $fileName,
                            'disk' => $disk,
                            'encrypted' => $encrypt,
                            'size_kb' => $fileSizeKb,
                        ]),
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Artisan CLI / Scheduler',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Abaikan bila audit_trails belum diinisialisasi
            }

            // Rotasi berkas lama (retensi cadangan)
            $this->cleanOldBackups($disk, $cleanDays);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal menyimpan berkas cadangan: '.$e->getMessage());
            Log::error('Database Backup Failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * Hapus berkas cadangan yang melampaui batas hari retensi.
     */
    protected function cleanOldBackups(string $disk, int $days): void
    {
        $cutoff = Carbon::now()->subDays($days);
        $files = Storage::disk($disk)->files('backups');
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($file));
            if ($lastModified->lt($cutoff)) {
                Storage::disk($disk)->delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Rotasi pembersihan: Dihapus {$deletedCount} berkas cadangan berusia lebih dari {$days} hari.");
        }
    }
}
