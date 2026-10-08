<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $projects = [
            [
                'id' => 'prj-101',
                'name' => 'Brand Identity & Web App Redesign',
                'client' => 'PT Nusantara Digital',
                'budget' => '180000000.00',
                'status' => 'IN_PROGRESS',
                'milestone' => 'Desain Sistem & Prototyping (75%)',
                'deadline' => '30 Oktober 2026',
                'assignments' => [
                    [
                        'employee_name' => 'Rian Ardiansyah',
                        'email' => 'rian.ardiansyah@intelcreative.co.id',
                        'role' => 'Lead UI/UX & Visual Asset Designer',
                        'task' => 'Pembuatan Design System Korporat & Prototyping Interaktif High-Fidelity',
                        'deadline' => '15 Oktober 2026',
                        'allocated_hours' => 40.0,
                        'logged_hours' => 28.5,
                        'status' => 'IN_PROGRESS',
                    ],
                    [
                        'employee_name' => 'Staff Employee',
                        'email' => 'staff@intelcreative.co.id',
                        'role' => 'Graphic & Motion Designer',
                        'task' => 'Produksi Video Teaser Brand & Animasi Logo Lottie',
                        'deadline' => '18 Oktober 2026',
                        'allocated_hours' => 25.0,
                        'logged_hours' => 15.0,
                        'status' => 'IN_PROGRESS',
                    ],
                ],
            ],
            [
                'id' => 'prj-102',
                'name' => 'Enterprise E-Commerce Engine',
                'client' => 'Mega Retail Global',
                'budget' => '450000000.00',
                'status' => 'IN_PROGRESS',
                'milestone' => 'Arsitektur Database & Payment Engine (40%)',
                'deadline' => '15 November 2026',
                'assignments' => [
                    [
                        'employee_name' => 'Dewi Safitri',
                        'email' => 'dewi.safitri@intelcreative.co.id',
                        'role' => 'Backend & Database Specialist',
                        'task' => 'Optimasi Skema Database PostgreSQL, Indexing Query, dan Auto-Journal Payment Gateway',
                        'deadline' => '20 Oktober 2026',
                        'allocated_hours' => 45.0,
                        'logged_hours' => 32.0,
                        'status' => 'IN_PROGRESS',
                    ],
                ],
            ],
        ];

        // Cari penugasan spesifik untuk pengguna yang sedang aktif login
        $myAssignments = [];
        foreach ($projects as $project) {
            foreach ($project['assignments'] as $assign) {
                if ($assign['email'] === $user->email) {
                    $myAssignments[] = array_merge($assign, [
                        'project_id' => $project['id'],
                        'project_name' => $project['name'],
                        'client' => $project['client'],
                        'project_status' => $project['status'],
                        'project_milestone' => $project['milestone'],
                    ]);
                }
            }
        }

        return Inertia::render('Operations/ProjectList', [
            'projects' => $projects,
            'my_assignments' => $myAssignments,
        ]);
    }
}
