<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $timesheets = [
            [
                'id' => 'ts-01',
                'employee_name' => 'Rian Ardiansyah',
                'email' => 'rian.ardiansyah@intelcreative.co.id',
                'project_name' => 'Brand Identity & Web App Redesign',
                'task' => 'Implementasi Komponen UI & Design System Minimalis',
                'billable_hours' => 6.5,
                'date' => '2026-10-08',
            ],
            [
                'id' => 'ts-02',
                'employee_name' => 'Rian Ardiansyah',
                'email' => 'rian.ardiansyah@intelcreative.co.id',
                'project_name' => 'Brand Identity & Web App Redesign',
                'task' => 'Ekspor Aset SVG & Optimasi Tipografi Navigasi',
                'billable_hours' => 5.0,
                'date' => '2026-10-07',
            ],
            [
                'id' => 'ts-03',
                'employee_name' => 'Rian Ardiansyah',
                'email' => 'rian.ardiansyah@intelcreative.co.id',
                'project_name' => 'Brand Identity & Web App Redesign',
                'task' => 'Penyusunan Arsitektur Token Warna & Variabel Spasi CSS',
                'billable_hours' => 7.0,
                'date' => '2026-10-06',
            ],
            [
                'id' => 'ts-04',
                'employee_name' => 'Rian Ardiansyah',
                'email' => 'rian.ardiansyah@intelcreative.co.id',
                'project_name' => 'Brand Identity & Web App Redesign',
                'task' => 'Wireframe Resolusi Desktop & Mobile Responsif',
                'billable_hours' => 6.0,
                'date' => '2026-10-05',
            ],
            [
                'id' => 'ts-05',
                'employee_name' => 'Rian Ardiansyah',
                'email' => 'rian.ardiansyah@intelcreative.co.id',
                'project_name' => 'Brand Identity & Web App Redesign',
                'task' => 'Review Bersama Klien & Penyesuaian Artboard UI',
                'billable_hours' => 4.0,
                'date' => '2026-10-04',
            ],
            [
                'id' => 'ts-06',
                'employee_name' => 'Dewi Safitri',
                'email' => 'dewi.safitri@intelcreative.co.id',
                'project_name' => 'Enterprise E-Commerce Engine',
                'task' => 'Perancangan Skema Database PostgreSQL & Partisi Tabel',
                'billable_hours' => 7.0,
                'date' => '2026-10-08',
            ],
            [
                'id' => 'ts-07',
                'employee_name' => 'Dewi Safitri',
                'email' => 'dewi.safitri@intelcreative.co.id',
                'project_name' => 'Enterprise E-Commerce Engine',
                'task' => 'Integrasi Payment Gateway & Auto-Journal Voucher Rekonsiliasi',
                'billable_hours' => 6.0,
                'date' => '2026-10-07',
            ],
            [
                'id' => 'ts-08',
                'employee_name' => 'Dewi Safitri',
                'email' => 'dewi.safitri@intelcreative.co.id',
                'project_name' => 'Enterprise E-Commerce Engine',
                'task' => 'Benchmark Query Performa Tinggi & Indexing Kolom Pencarian',
                'billable_hours' => 7.5,
                'date' => '2026-10-06',
            ],
            [
                'id' => 'ts-09',
                'employee_name' => 'Dewi Safitri',
                'email' => 'dewi.safitri@intelcreative.co.id',
                'project_name' => 'Enterprise E-Commerce Engine',
                'task' => 'Pembuatan Endpoint API & Validasi Form Request',
                'billable_hours' => 6.5,
                'date' => '2026-10-05',
            ],
            [
                'id' => 'ts-10',
                'employee_name' => 'Dewi Safitri',
                'email' => 'dewi.safitri@intelcreative.co.id',
                'project_name' => 'Enterprise E-Commerce Engine',
                'task' => 'Penulisan Unit Test & Mocking Transaksi Pembayaran',
                'billable_hours' => 5.0,
                'date' => '2026-10-04',
            ],
        ];

        $isManager = $user->can('employee.view-any');

        return Inertia::render('Operations/TimesheetList', [
            'timesheets' => $timesheets,
            'currentUserEmail' => $user->email,
            'isManager' => $isManager,
        ]);
    }
}
