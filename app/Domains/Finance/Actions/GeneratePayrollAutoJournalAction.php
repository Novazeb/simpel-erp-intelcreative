<?php

namespace App\Domains\Finance\Actions;

use App\Domains\Finance\Models\ChartOfAccount;
use App\Domains\Finance\Models\DisbursementVoucher;
use App\Domains\Finance\Models\JournalEntry;
use App\Domains\Finance\Models\JournalItem;
use App\Domains\Payroll\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneratePayrollAutoJournalAction
{
    /**
     * Menerbitkan Disbursement Voucher dan Auto Journal berpasangan (Double Entry)
     * Keseimbangan TC-FIN-01: Total Debit == Total Kredit
     */
    public function execute(PayrollPeriod $period): DisbursementVoucher
    {
        return DB::transaction(function () use ($period) {
            $slips = $period->slips()->with('items')->get();

            $totalTakeHomePay = (float) $slips->sum('take_home_pay');
            $totalBasicSalary = (float) $slips->sum('basic_salary_snapshot');

            // Agregasi items
            $totalOvertime = 0.00;
            $totalOtherAllowances = 0.00;
            $totalTax = 0.00;
            $totalBpjs = 0.00;

            foreach ($slips as $slip) {
                foreach ($slip->items as $item) {
                    $amount = (float) $item->amount;
                    if ($item->item_type === 'OVERTIME') {
                        $totalOvertime += $amount;
                    } elseif ($item->item_type === 'ALLOWANCE') {
                        $totalOtherAllowances += $amount;
                    } elseif ($item->item_type === 'TAX') {
                        $totalTax += $amount;
                    } elseif ($item->item_type === 'BENEFIT' || ($item->item_type === 'DEDUCTION' && str_contains($item->name, 'BPJS'))) {
                        $totalBpjs += $amount;
                    }
                }
            }

            // 1. Buat Voucher
            $voucherNumber = 'DISB-PAYROLL-'.$period->payment_date->format('Ym').'-'.strtoupper(Str::random(4));
            $voucher = DisbursementVoucher::updateOrCreate(
                ['payroll_period_id' => $period->id],
                [
                    'voucher_number' => $voucherNumber,
                    'payment_date' => $period->payment_date,
                    'total_amount' => $totalTakeHomePay,
                    'status' => 'RELEASED',
                ]
            );

            // 2. Buat Journal Entry
            $entryNumber = 'JRN-'.$period->payment_date->format('Ym').'-'.strtoupper(Str::random(4));

            // Hapus entry lama jika ada untuk idempotensi
            $existingEntry = JournalEntry::where('reference_type', 'PAYROLL_DISBURSEMENT')
                ->where('reference_id', $period->id)
                ->first();

            if ($existingEntry) {
                $existingEntry->delete();
            }

            $journalEntry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'reference_type' => 'PAYROLL_DISBURSEMENT',
                'reference_id' => $period->id,
                'transaction_date' => $period->payment_date,
                'description' => 'Penjurnalan Otomatis Penggajian: '.$period->name,
                'created_at' => now(),
            ]);

            // Dapatkan Akun COA
            $accExpenseSalary = ChartOfAccount::firstOrCreate(['code' => '5-1001'], ['name' => 'Beban Gaji Karyawan', 'type' => 'EXPENSE']);
            $accExpenseOvertime = ChartOfAccount::firstOrCreate(['code' => '5-1002'], ['name' => 'Beban Lembur Karyawan', 'type' => 'EXPENSE']);
            $accExpenseAllowance = ChartOfAccount::firstOrCreate(['code' => '5-1003'], ['name' => 'Beban Tunjangan Karyawan', 'type' => 'EXPENSE']);

            $accTaxPayable = ChartOfAccount::firstOrCreate(['code' => '2-2001'], ['name' => 'Utang PPh 21', 'type' => 'LIABILITY']);
            $accBpjsPayable = ChartOfAccount::firstOrCreate(['code' => '2-2002'], ['name' => 'Utang BPJS Ketenagakerjaan', 'type' => 'LIABILITY']);
            $accBank = ChartOfAccount::firstOrCreate(['code' => '1-1002'], ['name' => 'Kas Operasional Bank', 'type' => 'ASSET']);

            // Sisi DEBIT (Beban)
            $debits = [];
            if ($totalBasicSalary > 0) {
                $debits[] = ['account' => $accExpenseSalary, 'amount' => $totalBasicSalary];
            }
            if ($totalOvertime > 0) {
                $debits[] = ['account' => $accExpenseOvertime, 'amount' => $totalOvertime];
            }
            if ($totalOtherAllowances > 0) {
                $debits[] = ['account' => $accExpenseAllowance, 'amount' => $totalOtherAllowances];
            }

            // Sisi KREDIT (Utang & Pengeluaran Kas/Bank)
            $credits = [];
            if ($totalTax > 0) {
                $credits[] = ['account' => $accTaxPayable, 'amount' => $totalTax];
            }
            if ($totalBpjs > 0) {
                $credits[] = ['account' => $accBpjsPayable, 'amount' => $totalBpjs];
            }

            // Bank Payment = Selisih agar Total Debit == Total Kredit (TC-FIN-01 Strict Balance)
            $sumDebit = array_sum(array_column($debits, 'amount'));
            $sumExistingCredit = array_sum(array_column($credits, 'amount'));
            $netBankDisbursement = round($sumDebit - $sumExistingCredit, 2);

            if ($netBankDisbursement > 0) {
                $credits[] = ['account' => $accBank, 'amount' => $netBankDisbursement];
            }

            // Simpan baris Debit
            foreach ($debits as $deb) {
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $deb['account']->id,
                    'debit' => $deb['amount'],
                    'credit' => 0.00,
                    'created_at' => now(),
                ]);
            }

            // Simpan baris Kredit
            foreach ($credits as $crd) {
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $crd['account']->id,
                    'debit' => 0.00,
                    'credit' => $crd['amount'],
                    'created_at' => now(),
                ]);
            }

            return $voucher;
        });
    }
}
