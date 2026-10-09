<?php

namespace App\Http\Controllers\Api;

use App\Domains\Attendance\Models\Attendance;
use App\Domains\HR\Models\Employee;
use App\Domains\Shared\Services\SvgQrCodeGenerator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceQrController extends Controller
{
    /**
     * Tampilkan antarmuka Kios Tablet Lobby.
     */
    public function showKiosk(): Response
    {
        return Inertia::render('Kiosk/KioskScanner');
    }

    /**
     * Generate token QR dinamis (TOTP) untuk karyawan yang sedang login.
     */
    public function generateMyQr(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee
            ?? Employee::where('user_id', $user->id)->first()
            ?? Employee::where('email', $user->email)->first();

        if (! $employee) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Profil karyawan tidak ditemukan untuk akun ini.',
            ], 404);
        }

        if (empty($employee->qr_secret_key)) {
            $employee->update(['qr_secret_key' => Str::random(32)]);
        }

        $now = Carbon::now('Asia/Jakarta');
        $timestamp = $now->timestamp;
        $hash = hash_hmac('sha256', "{$employee->nik}|{$timestamp}", $employee->qr_secret_key);
        $payload = base64_encode("{$employee->nik}|{$timestamp}|{$hash}");

        $qrSvg = SvgQrCodeGenerator::generate($payload, 200);

        return response()->json([
            'status' => 'SUCCESS',
            'nik' => $employee->nik,
            'full_name' => $employee->full_name,
            'timestamp' => $timestamp,
            'qr_payload' => $payload,
            'qr_svg' => $qrSvg,
            'ttl_seconds' => 30,
            'expires_in_seconds' => 30,
        ]);
    }

    /**
     * Memproses Pindaian QR Code Dinamis / PIN Darurat dari Kios Tablet.
     */
    public function processScan(Request $request): JsonResponse
    {
        $request->validate([
            'scan_type' => 'required|in:QR_CODE,MANUAL_PIN',
            'qr_payload' => 'required_if:scan_type,QR_CODE|nullable|string',
            'nik' => 'required_if:scan_type,MANUAL_PIN|nullable|string',
            'pin' => 'required_if:scan_type,MANUAL_PIN|nullable|string|digits:6',
            'kiosk_device_id' => 'required|string|max:50',
        ]);

        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->format('H:i:s');
        $today = $now->toDateString();

        // 1. Evaluasi Aturan Window Time (08:30 - 09:15, 09:16 - 09:25, >09:25)
        $windowStart = '08:30:00';
        $onTimeEnd = '09:15:00';
        $windowEnd = '09:25:00';

        if ($currentTime < $windowStart) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Presensi belum dibuka. Window presensi dimulai pukul 08:30 WIB.',
                'sound' => 'ERROR_BEEP',
            ], 422);
        }

        if ($currentTime > $windowEnd) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Presensi telah ditutup (Batas 09:25 WIB). Silakan hubungi HR.',
                'sound' => 'ERROR_BEEP',
            ], 422);
        }

        // 2. Identifikasi Karyawan berdasarkan Tipe Pindaian
        $employee = null;

        if ($request->scan_type === 'QR_CODE') {
            $decoded = base64_decode($request->qr_payload, true);
            if ($decoded === false) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Format Kode QR tidak valid.',
                    'sound' => 'ERROR_BEEP',
                ], 400);
            }

            $parts = explode('|', $decoded);
            if (count($parts) !== 3) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Format Kode QR tidak valid.',
                    'sound' => 'ERROR_BEEP',
                ], 400);
            }

            [$nik, $timestamp, $totpHash] = $parts;

            // Validasi kadaluarsa timestamp (maksimal 30 detik toleransi)
            if (abs($now->timestamp - (int) $timestamp) > 30) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Kode QR sudah kadaluarsa (Expired). Harap gunakan QR terbaru.',
                    'sound' => 'ERROR_BEEP',
                ], 422);
            }

            $employee = Employee::where('nik', $nik)->first();
            if (! $employee) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Data karyawan tidak ditemukan.',
                    'sound' => 'ERROR_BEEP',
                ], 404);
            }

            // Validasi Hash TOTP dengan Secret Key Karyawan
            $expectedHash = hash_hmac('sha256', "{$nik}|{$timestamp}", (string) $employee->qr_secret_key);
            if (! hash_equals($expectedHash, $totpHash)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Autentikasi Kode QR gagal (Invalid Token).',
                    'sound' => 'ERROR_BEEP',
                ], 401);
            }

            // Anti-Replay Check via Cache
            $replayKey = 'qr_used_'.md5($request->qr_payload);
            if (Cache::has($replayKey)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Kode QR ini sudah pernah dipindai sebelumnya.',
                    'sound' => 'ERROR_BEEP',
                ], 422);
            }
            Cache::put($replayKey, true, 60); // Kunci selama 60 detik

        } else {
            // MANUAL_PIN
            $employee = Employee::where('nik', $request->nik)->first();
            if (! $employee || ! Hash::check((string) $request->pin, (string) $employee->kiosk_pin_hash)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'NIK atau PIN 6-Digit salah.',
                    'sound' => 'ERROR_BEEP',
                ], 401);
            }
        }

        // 3. Proteksi Atomic Lock per Karyawan (Pencegahan Double Scan)
        $lockKey = 'atomic_lock_attendance_'.$employee->id;
        $lock = Cache::lock($lockKey, 5);

        if (! $lock->get()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Pindaian Anda sedang diprosess. Mohon tunggu sejenak.',
                'sound' => 'ERROR_BEEP',
            ], 429);
        }

        try {
            // 4. Cek apakah sudah pernah absen hari ini
            $existing = Attendance::where('employee_id', $employee->id)
                ->where('work_date', $today)
                ->first();

            if ($existing) {
                $lock->release();

                return response()->json([
                    'status' => 'WARNING',
                    'employee_name' => $employee->full_name,
                    'nik' => $employee->nik,
                    'message' => "Anda sudah mencatatkan presensi hari ini pada {$existing->clock_in}.",
                    'sound' => 'WARNING_BEEP',
                ], 200);
            }

            // 5. Kalkulasi Status Presensi & Keterlambatan
            $status = ($currentTime <= $onTimeEnd) ? 'PRESENT' : 'LATE';
            $lateMinutes = 0;

            if ($status === 'LATE') {
                $scheduleStart = Carbon::createFromTimeString('08:30:00', 'Asia/Jakarta');
                $lateMinutes = (int) $scheduleStart->diffInMinutes($now);
            }

            // 6. Simpan Record Presensi
            Attendance::create([
                'employee_id' => $employee->id,
                'work_date' => $today,
                'clock_in' => $now,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'attendance_method' => $request->scan_type,
                'kiosk_device_id' => $request->kiosk_device_id,
                'notes' => "Presensi melalui Kios {$request->kiosk_device_id} metode {$request->scan_type}",
            ]);

            $lock->release();

            $statusText = ($status === 'PRESENT') ? 'Tepat Waktu' : "Terlambat ({$lateMinutes} menit)";
            $soundEffect = ($status === 'PRESENT') ? 'SUCCESS_BEEP' : 'WARNING_BEEP';

            return response()->json([
                'status' => 'SUCCESS',
                'employee_name' => $employee->full_name,
                'nik' => $employee->nik,
                'attendance_status' => $status,
                'clock_in' => $currentTime,
                'late_minutes' => $lateMinutes,
                'message' => "Presensi Berhasil! Status: {$statusText}",
                'sound' => $soundEffect,
            ], 200);

        } catch (\Throwable $e) {
            $lock->release();

            return response()->json([
                'status' => 'ERROR',
                'message' => 'Gagal memproses data ke database: '.$e->getMessage(),
                'sound' => 'ERROR_BEEP',
            ], 500);
        }
    }
}
