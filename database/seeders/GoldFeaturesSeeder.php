<?php

namespace Database\Seeders;

use App\Domains\Finance\Models\SaasSubscription;
use App\Domains\HR\Models\Employee;
use App\Domains\Procurement\Models\AssetItem;
use App\Domains\Project\Models\Client;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Task;
use App\Domains\Project\Models\Timesheet;
use App\Domains\Security\Models\InternalDocument;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GoldFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $rian = Employee::where('email', 'rian.ardiansyah@intelcreative.co.id')->first()
            ?? Employee::first();

        $dewi = Employee::where('email', 'dewi.safitri@intelcreative.co.id')->first();

        // 1. Master Klien & Proyek
        $client = Client::firstOrCreate(
            ['company_name' => 'PT Nusantara Digital'],
            ['email' => 'contact@nusantaradigital.id']
        );

        $project = Project::firstOrCreate(
            ['project_code' => 'PRJ-2026-001'],
            [
                'project_name' => 'Brand Identity & Web App Redesign',
                'client_id' => $client->id,
                'budget' => 180000000.00,
                'status' => 'IN_PROGRESS',
                'deadline' => '2026-10-30',
            ]
        );

        $task = Task::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Implementasi UI & Design System'],
            ['status' => 'IN_PROGRESS']
        );

        // 2. Timesheet Awal untuk Staf
        if ($rian) {
            Timesheet::firstOrCreate(
                [
                    'employee_id' => $rian->id,
                    'project_id' => $project->id,
                    'work_date' => '2026-10-08',
                ],
                [
                    'task_id' => $task->id,
                    'billable_hours' => 6.5,
                    'task_description' => 'Implementasi Komponen UI & Design System Minimalis',
                    'status' => 'APPROVED',
                    'approved_at' => now(),
                ]
            );

            Timesheet::firstOrCreate(
                [
                    'employee_id' => $rian->id,
                    'project_id' => $project->id,
                    'work_date' => '2026-10-07',
                ],
                [
                    'task_id' => $task->id,
                    'billable_hours' => 5.0,
                    'task_description' => 'Ekspor Aset SVG & Optimasi Tipografi Navigasi',
                    'status' => 'APPROVED',
                    'approved_at' => now(),
                ]
            );
        }

        if ($dewi) {
            Timesheet::firstOrCreate(
                [
                    'employee_id' => $dewi->id,
                    'project_id' => $project->id,
                    'work_date' => '2026-10-08',
                ],
                [
                    'task_id' => $task->id,
                    'billable_hours' => 7.0,
                    'task_description' => 'Perancangan Skema Database PostgreSQL & Partisi Tabel',
                    'status' => 'APPROVED',
                    'approved_at' => now(),
                ]
            );
        }

        // 3. Aset IT & Kantor
        AssetItem::firstOrCreate(
            ['asset_code' => 'AST-IT-2026-001'],
            [
                'name' => 'MacBook Pro M3 Max 36GB',
                'category' => 'HARDWARE',
                'serial_number' => 'C02G1234MD6R',
                'purchase_date' => '2026-01-10',
                'purchase_cost' => 36000000.00,
                'useful_life_years' => 3,
                'salvage_value' => 3600000.00,
                'current_book_value' => 36000000.00,
                'status' => 'IN_USE',
                'assigned_employee_id' => $rian?->id,
            ]
        );

        // 4. Subskripsi SaaS Mendekati Jatuh Tempo (5 Hari)
        SaasSubscription::firstOrCreate(
            ['software_name' => 'Adobe Creative Cloud Enterprise'],
            [
                'vendor_name' => 'Adobe Systems',
                'billing_cycle' => 'MONTHLY',
                'cost_per_cycle' => 1850000.00,
                'total_seats' => 5,
                'start_date' => '2026-01-01',
                'next_billing_date' => Carbon::today()->addDays(5)->toDateString(),
                'project_id' => $project->id,
                'status' => 'ACTIVE',
                'auto_renew' => true,
            ]
        );

        // 5. Dokumen Internal SOP
        if ($rian) {
            InternalDocument::firstOrCreate(
                ['document_number' => 'SOP-HR-2026-001'],
                [
                    'title' => 'Standar Operasional Presensi & Jam Kerja Agensi',
                    'category' => 'SOP',
                    'version' => '1.0',
                    'file_path' => '/documents/sop-presensi.pdf',
                    'requires_acknowledgment' => true,
                    'uploaded_by' => $rian->id,
                ]
            );
        }
    }
}
