<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GovernanceController extends Controller
{
    public function rbac()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return Inertia::render('Admin/RbacManagement', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function auditTrails()
    {
        $logs = DB::table('audit_trails')
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('Admin/AuditTrailList', [
            'logs' => $logs,
        ]);
    }
}
