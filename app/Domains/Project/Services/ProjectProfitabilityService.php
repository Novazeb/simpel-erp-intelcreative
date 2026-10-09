<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectProfitabilityService
{
    /**
     * Menghitung Margin Profitabilitas Proyek secara Deterministik & Real-Time
     */
    public function calculateProfitability(string $projectId): array
    {
        $project = Project::with(['client'])->findOrFail($projectId);
        $contractBudget = (float) $project->budget;

        // 1. Kalkulasi Direct Labor Cost berbasis Jam Timesheet x Cost Rate Gaji Karyawan per Jam
        // Hourly Rate = basic_salary / 173 (Standar Jam Kerja Bulanan Depnaker)
        $directLaborCost = DB::table('timesheets')
            ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
            ->where('timesheets.project_id', $projectId)
            ->where('timesheets.status', 'APPROVED')
            ->select(DB::raw('SUM(timesheets.billable_hours * (employees.basic_salary / 173.0)) as total_labor_cost'))
            ->value('total_labor_cost') ?? 0.00;

        // 2. Kalkulasi Operational Expenses (Reimbursement & Subskripsi SaaS yang dialokasikan ke proyek)
        $reimbursementExpenses = DB::table('reimbursements')
            ->where('project_id', $projectId)
            ->where('status', 'PAID')
            ->sum('total_amount');

        $saasExpenses = DB::table('saas_subscriptions')
            ->where('project_id', $projectId)
            ->where('status', 'ACTIVE')
            ->sum('cost_per_cycle');

        $totalOperationalExpenses = (float) $reimbursementExpenses + (float) $saasExpenses;

        // 3. Kalkulasi Total Biaya & Gross Profit Margin
        $totalCost = (float) $directLaborCost + $totalOperationalExpenses;
        $grossProfit = $contractBudget - $totalCost;
        $profitMarginPercentage = $contractBudget > 0 ? ($grossProfit / $contractBudget) * 100 : 0.00;

        return [
            'project_id' => $project->id,
            'project_code' => $project->project_code,
            'project_name' => $project->project_name,
            'client_name' => $project->client->company_name ?? 'N/A',
            'contract_budget' => $contractBudget,
            'direct_labor_cost' => round((float) $directLaborCost, 2),
            'operational_expenses' => round($totalOperationalExpenses, 2),
            'total_cost' => round($totalCost, 2),
            'gross_profit' => round($grossProfit, 2),
            'profit_margin_percentage' => round($profitMarginPercentage, 2),
            'health_status' => $profitMarginPercentage >= 30.0 ? 'HEALTHY' : ($profitMarginPercentage >= 10.0 ? 'WARNING' : 'CRITICAL'),
        ];
    }
}
