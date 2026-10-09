<?php

namespace Database\Seeders;

use App\Domains\HR\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class KioskScannerRoleSeeder extends Seeder
{
    /**
     * Jalankan seeding peran dan akun kios.
     */
    public function run(): void
    {
        // 1. Buat permission khusus pemindaian kios
        $permission = Permission::firstOrCreate([
            'name' => 'attendance.scan-qr',
            'guard_name' => 'web',
        ]);

        // 2. Buat role attendance-scanner
        $kioskRole = Role::firstOrCreate([
            'name' => 'attendance-scanner',
            'guard_name' => 'web',
        ]);

        // Berikan HANYA izin attendance.scan-qr ke role ini (Principle of Least Privilege)
        $kioskRole->syncPermissions([$permission]);

        // 3. Buat akun default untuk Tablet Kios Lobby 01
        $kioskUser = User::firstOrCreate(
            ['email' => 'kiosk.lobby01@intelcreative.co.id'],
            [
                'name' => 'Terminal Kios Lobby 01',
                'password' => Hash::make('KioskLobbySecure2026!'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        $kioskUser->assignRole($kioskRole);

        // 4. Provisioning default QR Secret dan PIN untuk seluruh karyawan aktif
        $employees = Employee::whereNull('qr_secret_key')->orWhereNull('kiosk_pin_hash')->get();
        foreach ($employees as $employee) {
            $employee->update([
                'qr_secret_key' => $employee->qr_secret_key ?: Str::random(32),
                'kiosk_pin_hash' => $employee->kiosk_pin_hash ?: Hash::make('123456'),
            ]);
        }

        if (isset($this->command)) {
            $this->command->info('Seeder KioskScannerRoleSeeder berhasil dijalankan. Account Kios: kiosk.lobby01@intelcreative.co.id');
        }
    }
}
