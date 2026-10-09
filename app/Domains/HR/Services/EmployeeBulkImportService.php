<?php

namespace App\Domains\HR\Services;

use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeBulkImportService
{
    /**
     * Memproses impor data massal karyawan dari array CSV dengan validasi berlapis dan transaksi atomik.
     * Jika ada satu baris cacat, seluruh proses di-rollback secara otomatis (umpanbalik3.md 2.C).
     *
     * Format header yang diharapkan:
     * nik,full_name,email,phone,department_code,designation_title,work_shift_name,employment_status,join_date,bank_name,bank_account_number,bank_account_holder,basic_salary,tax_status
     */
    public function import(array $rows): int
    {
        return DB::transaction(function () use ($rows) {
            $importedCount = 0;
            $seenNiks = [];
            $seenEmails = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;

                // 1. Validasi struktur baris
                $validator = Validator::make($row, [
                    'nik' => 'required|string',
                    'full_name' => 'required|string|max:150',
                    'email' => 'required|email|max:100',
                    'phone' => 'nullable|string|max:25',
                    'department_code' => 'required|string',
                    'designation_title' => 'required|string',
                    'work_shift_name' => 'required|string',
                    'employment_status' => 'required|in:PKWT,PKWTT,FREELANCE,PROBATION',
                    'join_date' => 'required|date',
                    'bank_name' => 'required|string',
                    'bank_account_number' => 'required|string',
                    'bank_account_holder' => 'required|string',
                    'basic_salary' => 'required|numeric|min:0',
                    'tax_status' => 'nullable|string|in:TK/0,TK/1,TK/2,TK/3,K/0,K/1,K/2,K/3',
                ]);

                if ($validator->fails()) {
                    throw ValidationException::withMessages([
                        'row_'.$rowNumber => "Baris ke-{$rowNumber} tidak valid: ".implode(', ', $validator->errors()->all()),
                    ]);
                }

                // 2. Validasi duplikasi internal dalam satu berkas impor
                if (in_array($row['nik'], $seenNiks)) {
                    throw ValidationException::withMessages([
                        'row_'.$rowNumber => "Baris ke-{$rowNumber}: Duplikasi NIK '{$row['nik']}' ditemukan dalam berkas impor yang sama.",
                    ]);
                }
                if (in_array($row['email'], $seenEmails)) {
                    throw ValidationException::withMessages([
                        'row_'.$rowNumber => "Baris ke-{$rowNumber}: Duplikasi Email '{$row['email']}' ditemukan dalam berkas impor yang sama.",
                    ]);
                }

                // 3. Validasi duplikasi terhadap database
                if (Employee::where('nik', $row['nik'])->exists()) {
                    throw ValidationException::withMessages([
                        'row_'.$rowNumber => "Baris ke-{$rowNumber}: NIK '{$row['nik']}' sudah terdaftar dalam sistem.",
                    ]);
                }
                if (Employee::where('email', $row['email'])->exists()) {
                    throw ValidationException::withMessages([
                        'row_'.$rowNumber => "Baris ke-{$rowNumber}: Email '{$row['email']}' sudah terdaftar dalam sistem.",
                    ]);
                }

                $seenNiks[] = $row['nik'];
                $seenEmails[] = $row['email'];

                // 4. Resolusi Relasi Departemen, Jabatan, dan Shift
                $department = Department::firstOrCreate(
                    ['code' => strtoupper(trim($row['department_code']))],
                    ['name' => strtoupper(trim($row['department_code'])).' Dept']
                );

                $designation = Designation::firstOrCreate(
                    ['department_id' => $department->id, 'title' => trim($row['designation_title'])],
                    ['title' => trim($row['designation_title'])]
                );

                $workShift = WorkShift::firstOrCreate(
                    ['name' => trim($row['work_shift_name'])],
                    ['start_time' => '09:00:00', 'end_time' => '18:00:00', 'late_tolerance_minutes' => 15]
                );

                // 5. Buat data karyawan
                Employee::create([
                    'nik' => trim($row['nik']),
                    'full_name' => trim($row['full_name']),
                    'email' => strtolower(trim($row['email'])),
                    'phone' => $row['phone'] ?? null,
                    'department_id' => $department->id,
                    'designation_id' => $designation->id,
                    'work_shift_id' => $workShift->id,
                    'employment_status' => $row['employment_status'],
                    'join_date' => $row['join_date'],
                    'bank_name' => trim($row['bank_name']),
                    'bank_account_number' => trim($row['bank_account_number']),
                    'bank_account_holder' => trim($row['bank_account_holder']),
                    'basic_salary' => (float) $row['basic_salary'],
                    'tax_status' => $row['tax_status'] ?? 'TK/0',
                ]);

                $importedCount++;
            }

            return $importedCount;
        });
    }

    /**
     * Parsing isi file CSV menjadi array asosiatif
     */
    public function parseCsv(string $csvContent): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($csvContent));
        if (empty($lines)) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($h) => trim($h), $header);

        $data = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $row = str_getcsv($line);
            if (count($row) === count($header)) {
                $data[] = array_combine($header, $row);
            }
        }

        return $data;
    }
}
