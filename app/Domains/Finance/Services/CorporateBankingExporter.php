<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\Models\DisbursementVoucher;

class CorporateBankingExporter
{
    /**
     * Ekspor ke format CSV / Teks resmi KlikBCA Bisnis
     */
    public function exportBcaFormat(DisbursementVoucher $voucher): string
    {
        $voucher->load('payrollPeriod.slips.employee');
        $slips = $voucher->payrollPeriod->slips;

        $lines = [];
        // Header baris informasi total
        $totalAmount = (int) $voucher->total_amount;
        $totalRecords = $slips->count();
        $date = $voucher->payment_date->format('Ymd');

        $lines[] = "H,{$date},{$totalRecords},{$totalAmount}";

        foreach ($slips as $slip) {
            $emp = $slip->employee;
            $accNumber = preg_replace('/[^0-9]/', '', $emp->bank_account_number);
            $amount = (int) $slip->take_home_pay;
            $name = strtoupper(substr($emp->bank_account_holder, 0, 30));

            // Detail record: D,Account,Amount,Name,Remark
            $lines[] = "D,{$accNumber},{$amount},{$name},GAJI {$voucher->payrollPeriod->name}";
        }

        return implode("\r\n", $lines);
    }

    /**
     * Ekspor ke format Mandiri Cash Management (MCM) CSV
     */
    public function exportMandiriMcmFormat(DisbursementVoucher $voucher): string
    {
        $voucher->load('payrollPeriod.slips.employee');
        $slips = $voucher->payrollPeriod->slips;

        $lines = [];
        // Header kolom MCM
        $lines[] = "Beneficiary Account Number,Beneficiary Name,Beneficiary Bank,Amount,Currency,Remark";

        foreach ($slips as $slip) {
            $emp = $slip->employee;
            $accNumber = preg_replace('/[^0-9]/', '', $emp->bank_account_number);
            $amount = number_format((float) $slip->take_home_pay, 2, '.', '');
            $name = str_replace('"', '""', $emp->bank_account_holder);
            $bank = strtoupper($emp->bank_name);

            $lines[] = "\"{$accNumber}\",\"{$name}\",\"{$bank}\",{$amount},IDR,\"PAYROLL {$voucher->payrollPeriod->name}\"";
        }

        return implode("\r\n", $lines);
    }
}

