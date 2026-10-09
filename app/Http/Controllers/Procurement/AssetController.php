<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index()
    {
        $assets = [
            [
                'id' => 'ast-001',
                'asset_code' => 'AST-MBP-2026-001',
                'name' => 'MacBook Pro 16" M3 Max (64GB RAM)',
                'serial_number' => 'C02G90XXMD6M',
                'assigned_to' => 'Rian Ardiansyah (Engineering)',
                'acquisition_cost' => '54000000.00',
                'status' => 'IN_USE',
            ],
            [
                'id' => 'ast-002',
                'asset_code' => 'AST-CAM-2026-005',
                'name' => 'Sony A7 IV + Lens FE 24-70mm GM II',
                'serial_number' => 'SN-398201948',
                'assigned_to' => 'Studio Kreatif & Media',
                'acquisition_cost' => '62000000.00',
                'status' => 'IN_USE',
            ],
        ];

        return Inertia::render('Procurement/AssetList', [
            'assets' => $assets,
        ]);
    }
}
