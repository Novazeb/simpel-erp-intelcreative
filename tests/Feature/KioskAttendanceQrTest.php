<?php

namespace Tests\Feature;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\HR\Models\Employee;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\KioskScannerRoleSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StaffSimulationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KioskAttendanceQrTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
        $this->seed(StaffSimulationSeeder::class);
        $this->seed(KioskScannerRoleSeeder::class);

        // Ambil salah satu karyawan staf (Rian Ardiansyah)
        $this->employee = Employee::where('email', 'rian.ardiansyah@intelcreative.co.id')->first()
            ?? Employee::first();
        if (! $this->employee->qr_secret_key) {
            $this->employee->update([
                'qr_secret_key' => 'secret_test_key_1234567890123456',
                'kiosk_pin_hash' => Hash::make('123456'),
            ]);
        }
    }

    /**
     * Helper untuk membuat payload QR valid pada timestamp tertentu.
     */
    protected function generateQrPayload(Employee $emp, int $timestamp): string
    {
        $hash = hash_hmac('sha256', "{$emp->nik}|{$timestamp}", (string) $emp->qr_secret_key);

        return base64_encode("{$emp->nik}|{$timestamp}|{$hash}");
    }

    /**
     * TC-QR-01: Scan QR Code valid antara 08:30 – 09:15 WIB.
     * Hasil yang Diharapkan: 200 OK, status: PRESENT, late_minutes: 0, sound: SUCCESS_BEEP.
     */
    public function test_tc_qr_01_valid_qr_scan_on_time(): void
    {
        // Set waktu simulasi ke 08:45:00 WIB
        Carbon::setTestNow(Carbon::parse('2026-10-09 08:45:00', 'Asia/Jakarta'));

        $timestamp = Carbon::now('Asia/Jakarta')->timestamp;
        $payload = $this->generateQrPayload($this->employee, $timestamp);

        $response = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'QR_CODE',
            'qr_payload' => $payload,
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'SUCCESS',
            'nik' => $this->employee->nik,
            'attendance_status' => 'PRESENT',
            'late_minutes' => 0,
            'sound' => 'SUCCESS_BEEP',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'PRESENT',
            'attendance_method' => 'QR_CODE',
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);

        $record = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('work_date', '2026-10-09')
            ->first();
        $this->assertNotNull($record);
        $this->assertEquals(0, $record->late_minutes);
    }

    /**
     * TC-QR-02: Scan QR Code valid antara 09:16 – 09:25 WIB.
     * Hasil yang Diharapkan: 200 OK, status: LATE, late_minutes > 0, sound: WARNING_BEEP.
     */
    public function test_tc_qr_02_valid_qr_scan_late_window(): void
    {
        // Set waktu simulasi ke 09:20:00 WIB (50 menit dari 08:30)
        Carbon::setTestNow(Carbon::parse('2026-10-09 09:20:00', 'Asia/Jakarta'));

        $timestamp = Carbon::now('Asia/Jakarta')->timestamp;
        $payload = $this->generateQrPayload($this->employee, $timestamp);

        $response = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'QR_CODE',
            'qr_payload' => $payload,
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'SUCCESS',
            'attendance_status' => 'LATE',
            'late_minutes' => 50,
            'sound' => 'WARNING_BEEP',
        ]);
    }

    /**
     * TC-QR-03: Scan QR Code di luar batas waktu (sebelum 08:30 dan setelah 09:25 WIB).
     * Hasil yang Diharapkan: 422 Unprocessable Entity, sound: ERROR_BEEP.
     */
    public function test_tc_qr_03_scan_outside_window(): void
    {
        // 1. Sebelum jam buka (08:15:00 WIB)
        Carbon::setTestNow(Carbon::parse('2026-10-09 08:15:00', 'Asia/Jakarta'));
        $payload = $this->generateQrPayload($this->employee, Carbon::now('Asia/Jakarta')->timestamp);

        $responseEarly = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'QR_CODE',
            'qr_payload' => $payload,
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);

        $responseEarly->assertStatus(422);
        $responseEarly->assertJsonFragment(['sound' => 'ERROR_BEEP']);

        // 2. Setelah jam tutup (09:30:00 WIB)
        Carbon::setTestNow(Carbon::parse('2026-10-09 09:30:00', 'Asia/Jakarta'));
        $payloadLate = $this->generateQrPayload($this->employee, Carbon::now('Asia/Jakarta')->timestamp);

        $responseLate = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'QR_CODE',
            'qr_payload' => $payloadLate,
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);

        $responseLate->assertStatus(422);
        $responseLate->assertJsonFragment(['sound' => 'ERROR_BEEP']);
    }

    /**
     * TC-QR-04: Pindaian ganda QR (Double Scan / Replay).
     * Hasil yang Diharapkan: Pindaian kedua diblokir (Anti-Replay 422 atau WARNING sudah absen).
     */
    public function test_tc_qr_04_double_scan_and_replay_protection(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09 08:50:00', 'Asia/Jakarta'));
        $timestamp = Carbon::now('Asia/Jakarta')->timestamp;
        $payload = $this->generateQrPayload($this->employee, $timestamp);

        // Pindaian pertama sukses
        $res1 = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'QR_CODE',
            'qr_payload' => $payload,
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);
        $res1->assertStatus(200);

        // Pindaian kedua dengan payload yang sama persis (Anti-Replay)
        $res2 = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'QR_CODE',
            'qr_payload' => $payload,
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);
        $res2->assertStatus(422);
        $res2->assertJson(['message' => 'Kode QR ini sudah pernah dipindai sebelumnya.']);
    }

    /**
     * TC-QR-05: Pindaian QR yang sudah kadaluarsa (Expired > 30 detik).
     * Hasil yang Diharapkan: 422 Unprocessable Entity, pesan "Kode QR sudah kadaluarsa".
     */
    public function test_tc_qr_05_expired_qr_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09 08:50:00', 'Asia/Jakarta'));
        // Generate payload dengan timestamp 45 detik yang lalu
        $oldTimestamp = Carbon::now('Asia/Jakarta')->timestamp - 45;
        $payload = $this->generateQrPayload($this->employee, $oldTimestamp);

        $response = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'QR_CODE',
            'qr_payload' => $payload,
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'ERROR',
            'message' => 'Kode QR sudah kadaluarsa (Expired). Harap gunakan QR terbaru.',
        ]);
    }

    /**
     * TC-QR-06: Presensi Darurat NIK + PIN 6-Digit valid.
     * Hasil yang Diharapkan: 200 OK, attendance_method: MANUAL_PIN.
     */
    public function test_tc_qr_06_emergency_manual_pin_scan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09 09:05:00', 'Asia/Jakarta'));

        $response = $this->postJson('/api/v1/attendance/qr-scan', [
            'scan_type' => 'MANUAL_PIN',
            'nik' => $this->employee->nik,
            'pin' => '123456',
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'SUCCESS',
            'nik' => $this->employee->nik,
            'attendance_status' => 'PRESENT',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'attendance_method' => 'MANUAL_PIN',
            'kiosk_device_id' => 'KIOSK-LOBBY-01',
        ]);
    }

    /**
     * TC-QR-07: Eksekusi command attendance:close-daily-window pada pukul 09:26 WIB.
     * Hasil yang Diharapkan: Karyawan tanpa rekaman presensi otomatis ditandai ABSENT.
     */
    public function test_tc_qr_07_close_daily_window_marks_absent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09 09:26:00', 'Asia/Jakarta'));

        // Pastikan tabel attendances bersih untuk hari ini
        Attendance::where('work_date', '2026-10-09')->delete();

        $exitCode = Artisan::call('attendance:close-daily-window');
        $this->assertEquals(0, $exitCode);

        // Karyawan harus tercatat sebagai ABSENT
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'ABSENT',
            'attendance_method' => 'SYSTEM_AUTO',
        ]);

        $absentRecord = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('work_date', '2026-10-09')
            ->first();
        $this->assertNotNull($absentRecord);
    }
}
