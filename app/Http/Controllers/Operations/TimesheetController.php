<?php

namespace App\Http\Controllers\Operations;

use App\Domains\HR\Models\Employee;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Task;
use App\Domains\Project\Models\Timesheet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isManager = $user->can('employee.view-any');

        $query = Timesheet::with(['employee', 'project', 'task'])->latest('work_date');

        if (! $isManager) {
            $employee = $user->employee
                ?? Employee::where('user_id', $user->id)->first()
                ?? Employee::where('email', $user->email)->first();

            $query->where('employee_id', $employee?->id ?? '00000000-0000-0000-0000-000000000000');
        }

        $records = $query->get();

        $timesheets = $records->map(function ($ts) {
            return [
                'id' => (string) $ts->id,
                'employee_name' => $ts->employee?->full_name ?? 'Karyawan',
                'email' => $ts->employee?->email ?? '',
                'project_name' => $ts->project?->project_name ?? 'Proyek Internal',
                'task' => $ts->task_description,
                'billable_hours' => (float) $ts->billable_hours,
                'date' => is_string($ts->work_date) ? substr($ts->work_date, 0, 10) : $ts->work_date?->format('Y-m-d'),
                'status' => $ts->status,
            ];
        })->toArray();

        $projects = Project::all(['id', 'project_name', 'project_code']);
        $tasks = Task::all(['id', 'project_id', 'name']);

        return Inertia::render('Operations/TimesheetList', [
            'timesheets' => $timesheets,
            'projects' => $projects,
            'tasks' => $tasks,
            'currentUserEmail' => $user->email,
            'isManager' => $isManager,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|uuid|exists:projects,id',
            'task_id' => 'nullable|uuid|exists:tasks,id',
            'work_date' => 'required|date',
            'billable_hours' => 'required|numeric|min:0.5|max:24',
            'task_description' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $employee = $user->employee
            ?? Employee::where('user_id', $user->id)->first()
            ?? Employee::where('email', $user->email)->first();

        if (! $employee) {
            abort(404, 'Profil karyawan tidak ditemukan.');
        }

        $timesheet = Timesheet::create([
            'employee_id' => $employee->id,
            'project_id' => $validated['project_id'],
            'task_id' => $validated['task_id'] ?? null,
            'work_date' => $validated['work_date'],
            'billable_hours' => $validated['billable_hours'],
            'task_description' => $validated['task_description'],
            'status' => 'APPROVED',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Log jam kerja proyek berhasil disimpan.',
                'data' => $timesheet,
            ], 201);
        }

        return redirect()->back()->with('success', 'Log jam kerja proyek berhasil disimpan.');
    }
}
