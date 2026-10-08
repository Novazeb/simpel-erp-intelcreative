<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            ['code' => '1-1001', 'name' => 'Kas Operasional Kantor', 'type' => 'ASSET'],
            ['code' => '1-1002', 'name' => 'Kas Operasional Bank', 'type' => 'ASSET'],
            ['code' => '2-2001', 'name' => 'Utang PPh 21', 'type' => 'LIABILITY'],
            ['code' => '2-2002', 'name' => 'Utang BPJS Ketenagakerjaan', 'type' => 'LIABILITY'],
            ['code' => '2-2003', 'name' => 'Utang BPJS Kesehatan', 'type' => 'LIABILITY'],
            ['code' => '2-2004', 'name' => 'Utang Gaji Karyawan', 'type' => 'LIABILITY'],
            ['code' => '5-1001', 'name' => 'Beban Gaji Karyawan', 'type' => 'EXPENSE'],
            ['code' => '5-1002', 'name' => 'Beban Lembur Karyawan', 'type' => 'EXPENSE'],
            ['code' => '5-1003', 'name' => 'Beban Tunjangan Karyawan', 'type' => 'EXPENSE'],
            ['code' => '5-1004', 'name' => 'Beban Iuran BPJS Kantor', 'type' => 'EXPENSE'],
        ];

        foreach ($accounts as $account) {
            DB::table('chart_of_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'created_at' => now(),
                ]
            );
        }
    }
}
