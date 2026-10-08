<?php

namespace App\Http\Controllers\Attendance;

use App\Domains\Attendance\Actions\RecordAttendanceAction;
use App\Domains\Attendance\Models\Attendance;
use App\Domains\HR\Models\Employee;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Attendance::with('employee')->latest('work_date');

        if (! $user->can('attendance.view-all')) {
            $employee = Employee::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            $query->where('employee_id', $employee?->id ?? '00000000-0000-0000-0000-000000000000');
        }

        $attendances = $query->paginate(20);

        return Inertia::render('Attendance/Timesheet', [
            'attendances' => $attendances,
        ]);
    }

    public function clockIn(Request $request, RecordAttendanceAction $action)
    {
        $request->validate([
            'timestamp' => 'nullable|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil data karyawan tidak ditemukan.',
            ], 404);
        }

        $employeeId = $employee->id;

        // Terapkan kunci atomik selama 5 detik khusus untuk ID karyawan ini (umpanbalik4.md poin 2)
        $lock = Cache::lock("clock_in_employee_{$employeeId}", 5);

        if ($lock->get()) {
            try {
                $timestamp = $request->timestamp ? Carbon::parse($request->timestamp) : now();

                $attendance = $action->execute($employee, $timestamp);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Presensi masuk berhasil dicatat.',
                        'data' => [
                            'status' => $attendance->status,
                            'late_minutes' => $attendance->late_minutes,
                        ],
                    ]);
                }

                return redirect()->back()->with('success', 'Presensi berhasil dicatat.');
            } finally {
                // Selalu lepaskan kunci setelah proses selesai atau gagal
                $lock->release();
            }
        }

        // Tolak permintaan ganda simultan dengan status 429 Too Many Requests
        return response()->json([
            'message' => 'Sistem sedang memproses presensi Anda, mohon tunggu.',
        ], 429);
    }
}
