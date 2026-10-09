<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi skema database.
     */
    public function up(): void
    {
        // 1. Tambah atribut QR Secret dan Kiosk PIN pada tabel employees
        Schema::table('employees', function (Blueprint $table) {
            $table->string('qr_secret_key', 64)->nullable()->after('email');
            $table->string('kiosk_pin_hash', 255)->nullable()->after('qr_secret_key');
        });

        // 2. Tambah kolom attendance_method, kiosk_device_id, dan notes pada tabel attendances
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('attendance_method', 30)->default('QR_CODE')->after('status');
            $table->string('kiosk_device_id', 50)->nullable()->after('attendance_method');
            $table->text('notes')->nullable()->after('kiosk_device_id');
        });
    }

    /**
     * Batalkan migrasi skema database.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['qr_secret_key', 'kiosk_pin_hash']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['attendance_method', 'kiosk_device_id', 'notes']);
        });
    }
};
