<?php

namespace App\Http\Controllers\HR;

use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use App\Domains\HR\Services\EmployeeBulkImportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Employee::with(['department', 'designation', 'workShift'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(25)
            ->withQueryString()
            ->through(fn ($emp) => [
                'id' => $emp->id,
                'nik' => $emp->nik,
                'full_name' => $emp->full_name,
                'email' => $emp->email,
                'department' => $emp->department?->name,
                'designation' => $emp->designation?->title,
                'employment_status' => $emp->employment_status,
                'basic_salary' => $request->user()->can('employee.view-salary') ? (string) $emp->basic_salary : null,
            ]);

        return Inertia::render('HR/EmployeeIndex', [
            'employees' => $employees,
            'filters' => [
                'search' => $search,
            ],
            'departments' => Department::all(['id', 'name']),
            'designations' => Designation::all(['id', 'title', 'department_id']),
            'work_shifts' => WorkShift::all(['id', 'name', 'start_time', 'end_time']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', Rule::unique('employees', 'nik')->whereNull('deleted_at')],
            'full_name' => 'required|string|max:150',
            'email' => ['required', 'email', Rule::unique('employees', 'email')->whereNull('deleted_at')],
            'phone' => 'nullable|string|max:25',
            'department_id' => 'required|uuid|exists:departments,id',
            'designation_id' => 'required|uuid|exists:designations,id',
            'work_shift_id' => 'required|uuid|exists:work_shifts,id',
            'employment_status' => 'required|in:PKWT,PKWTT,FREELANCE,PROBATION',
            'join_date' => 'required|date',
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|string',
            'bank_account_holder' => 'required|string',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        $employee = Employee::create($validated);

        return redirect()->back()->with('success', 'Karyawan berhasil didaftarkan.');
    }

    public function importCsv(Request $request, EmployeeBulkImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $rows = $importService->parseCsv($content);

        try {
            $count = $importService->import($rows);

            return redirect()->back()->with('success', "Berhasil mengimpor {$count} data karyawan secara massal.");
        } catch (ValidationException $e) {
            $messages = implode(' ', array_map(fn ($m) => implode(' ', $m), $e->errors()));

            return redirect()->back()->with('error', "Gagal mengimpor: {$messages}");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Gagal mengimpor: {$e->getMessage()}");
        }
    }

    public function destroy(Employee $employee)
    {
        // Soft delete karyawan (TC-HR-01)
        $employee->delete();

        return redirect()->back()->with('success', 'Karyawan berhasil diarsipkan (soft deleted).');
    }
}
