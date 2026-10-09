<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Finance\Models\ChartOfAccount;
use App\Domains\Finance\Models\JournalEntry;
use App\Domains\Finance\Models\JournalItem;
use App\Domains\Finance\Models\Reimbursement;
use App\Domains\HR\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReimbursementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isFinance = $user->can('finance.voucher-release') || $user->can('finance.journal-view');

        $query = Reimbursement::with(['employee', 'project', 'items'])->latest('claim_date');

        if (! $isFinance) {
            $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();
            $query->where('employee_id', $employee?->id ?? '00000000-0000-0000-0000-000000000000');
        }

        $reimbursements = $query->paginate(20);

        return Inertia::render('Finance/ReimbursementList', [
            'reimbursements' => $reimbursements,
            'isFinance' => $isFinance,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'claim_date' => 'required|date',
            'project_id' => 'nullable|uuid|exists:projects,id',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:1',
            'items.*.receipt' => 'nullable|file|max:2048',
        ]);

        $reimbursement = DB::transaction(function () use ($validated, $request) {
            $employee = $request->user()->employee
                ?? Employee::where('user_id', $request->user()->id)->first()
                ?? Employee::where('email', $request->user()->email)->first();

            if (! $employee) {
                abort(404, 'Profil karyawan tidak ditemukan.');
            }

            $claimNumber = 'REIMB-'.date('Ym').'-'.str_pad((string) rand(1, 999), 3, '0', STR_PAD_LEFT);

            $reimb = Reimbursement::create([
                'claim_number' => $claimNumber,
                'employee_id' => $employee->id,
                'project_id' => $validated['project_id'] ?? null,
                'claim_date' => $validated['claim_date'],
                'title' => $validated['title'],
                'total_amount' => 0.00,
                'status' => 'SUBMITTED',
            ]);

            $total = 0.00;
            foreach ($validated['items'] as $item) {
                $path = isset($item['receipt']) && is_object($item['receipt'])
                    ? $item['receipt']->store('receipts', 'public')
                    : null;

                $reimb->items()->create([
                    'expense_date' => $validated['claim_date'],
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'receipt_path' => $path,
                ]);
                $total += (float) $item['amount'];
            }

            $reimb->update(['total_amount' => $total]);

            return $reimb;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan reimbursement berhasil dikirim.',
                'data' => $reimbursement,
            ], 201);
        }

        return redirect()->back()->with('success', 'Pengajuan reimbursement berhasil dikirim.');
    }

    public function approveByFinance(Request $request, string $id)
    {
        $reimbursement = Reimbursement::findOrFail($id);

        DB::transaction(function () use ($reimbursement, $request) {
            $financeEmployee = $request->user()->employee
                ?? Employee::where('user_id', $request->user()->id)->first()
                ?? Employee::where('email', $request->user()->email)->first();

            $reimbursement->update([
                'status' => 'PAID',
                'approved_by_finance' => $financeEmployee?->id,
            ]);

            // Terbitkan Voucher & Auto-Journal Kas Kecil (Petty Cash)
            $pettyCashAcc = ChartOfAccount::firstOrCreate(
                ['code' => '1-1001'],
                ['name' => 'Kas Operasional Kantor', 'type' => 'ASSET']
            );
            $expenseAcc = ChartOfAccount::firstOrCreate(
                ['code' => '5-2001'],
                ['name' => 'Beban Operasional & Reimbursement', 'type' => 'EXPENSE']
            );

            $journal = JournalEntry::create([
                'entry_number' => 'JRN-REIMB-'.$reimbursement->claim_number,
                'reference_type' => 'REIMBURSEMENT',
                'reference_id' => $reimbursement->id,
                'transaction_date' => now()->toDateString(),
                'description' => "Pencairan Reimbursement Kas Kecil: {$reimbursement->title} ({$reimbursement->claim_number})",
            ]);

            JournalItem::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $expenseAcc->id,
                'debit' => $reimbursement->total_amount,
                'credit' => 0.00,
            ]);

            JournalItem::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pettyCashAcc->id,
                'debit' => 0.00,
                'credit' => $reimbursement->total_amount,
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reimbursement disetujui & dicairkan dari Kas Kecil.',
                'data' => $reimbursement->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Reimbursement disetujui & dicairkan dari Kas Kecil.');
    }
}
