<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Finance\Models\DisbursementVoucher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = DisbursementVoucher::with('payrollPeriod')
            ->latest()
            ->paginate(15);

        return Inertia::render('Finance/VoucherList', [
            'vouchers' => $vouchers,
        ]);
    }

    public function show(DisbursementVoucher $voucher)
    {
        $voucher->load([
            'payrollPeriod',
            'journalEntry.items.account',
        ]);

        $formattedJournal = null;
        if ($voucher->journalEntry) {
            $formattedJournal = [
                'entry_number' => $voucher->journalEntry->entry_number,
                'transaction_date' => $voucher->journalEntry->transaction_date->toDateString(),
                'description' => $voucher->journalEntry->description,
                'items' => $voucher->journalEntry->items->map(fn ($item) => [
                    'account_code' => $item->account?->code,
                    'name' => $item->account?->name,
                    'debit' => (string) $item->debit,
                    'credit' => (string) $item->credit,
                ]),
            ];
        }

        return Inertia::render('Finance/VoucherDetail', [
            'voucher' => [
                'id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'payment_date' => $voucher->payment_date->toDateString(),
                'total_amount' => (string) $voucher->total_amount,
                'status' => $voucher->status,
                'journal_entry' => $formattedJournal,
            ],
        ]);
    }

    public function exportBca(DisbursementVoucher $voucher, \App\Domains\Finance\Services\CorporateBankingExporter $exporter)
    {
        $content = $exporter->exportBcaFormat($voucher);
        $fileName = "BCA-PAYROLL-{$voucher->voucher_number}.txt";

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function exportMandiri(DisbursementVoucher $voucher, \App\Domains\Finance\Services\CorporateBankingExporter $exporter)
    {
        $content = $exporter->exportMandiriMcmFormat($voucher);
        $fileName = "MANDIRI-MCM-{$voucher->voucher_number}.csv";

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function reconcile(DisbursementVoucher $voucher)
    {
        $voucher->update(['status' => 'RECONCILED']);

        return redirect()->back()->with('success', 'Voucher berhasil direkonsiliasi.');
    }
}
