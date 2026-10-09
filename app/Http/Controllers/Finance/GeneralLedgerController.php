<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Finance\Models\ChartOfAccount;
use App\Domains\Finance\Models\JournalEntry;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class GeneralLedgerController extends Controller
{
    public function index()
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        $recentJournals = JournalEntry::with('items.account')
            ->latest('transaction_date')
            ->take(10)
            ->get();

        return Inertia::render('Finance/GeneralLedger', [
            'accounts' => $accounts,
            'recent_journals' => $recentJournals,
        ]);
    }
}
