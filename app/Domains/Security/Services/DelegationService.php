<?php

namespace App\Domains\Security\Services;

use App\Domains\HR\Models\Employee;
use App\Domains\Security\Models\Delegation;
use Carbon\Carbon;

class DelegationService
{
    /**
     * Memeriksa apakah actingEmployee berhak bertindak atas nama targetEmployee untuk scope tertentu pada tanggal yang diberikan.
     */
    public function canActAs(Employee $actingEmployee, Employee $targetEmployee, string $scope = 'ALL_APPROVALS', ?string $date = null): bool
    {
        if ($actingEmployee->id === $targetEmployee->id) {
            return true;
        }

        $checkDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        return Delegation::where('delegator_id', $targetEmployee->id)
            ->where('delegatee_id', $actingEmployee->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $checkDate)
            ->whereDate('end_date', '>=', $checkDate)
            ->where(function ($query) use ($scope) {
                $query->where('scope', 'ALL_APPROVALS')
                    ->orWhere('scope', $scope);
            })
            ->exists();
    }

    /**
     * Mengambil profil Acting Manager aktif untuk seorang pejabat tertentu.
     */
    public function getActiveDelegate(Employee $delegator, string $scope = 'ALL_APPROVALS', ?string $date = null): ?Employee
    {
        $checkDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $delegation = Delegation::with('delegatee')
            ->where('delegator_id', $delegator->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $checkDate)
            ->whereDate('end_date', '>=', $checkDate)
            ->where(function ($query) use ($scope) {
                $query->where('scope', 'ALL_APPROVALS')
                    ->orWhere('scope', $scope);
            })
            ->first();

        return $delegation?->delegatee;
    }
}
