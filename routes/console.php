<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Enterprise Scheduled Tasks & Production Maintenance
|--------------------------------------------------------------------------
|
| 1. Backup Basis Data Terenkripsi: Dijalankan setiap hari pukul 02:00 WIB
| 2. Arsip Jejak Audit (Cold Storage): Dijalankan setiap tanggal 1 pukul 01:00 WIB
|
*/

Schedule::command('db:backup --encrypt --clean-older-days=30')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

Schedule::command('audit:archive --months=12')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/audit-archive.log'));
