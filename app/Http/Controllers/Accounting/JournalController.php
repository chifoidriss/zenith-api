<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index()
    {
        return Journal::paginate(20);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fiscal_year_id'=>'required|exists:fiscal_years,id',
            'allow_reconciliation'=>'nullable|boolean',
            'deprecated'=>'nullable|boolean',
            'account_type_id'=>'required|numeric',
        ]);

        $journal = Journal::firstOrNew([
            'id' => $request->input('id')
        ]);
        $journal->fill($request->only([
            'code',
            'name',
            'deprecated',
            'allow_reconciliation',
            'account_type_id',
        ]));
        $journal->save();

        return $this->show($journal->id);
    }

    public function show($id)
    {
        return Journal::with([
            'partner',
            'chartAccount',
            'fiscalYear',
            'invoice',
            'journal',
            'article',
        ])->findOrFail($id);
    }

    public function destroy($id)
    {
        $journal = Journal::findOrFail($id);
        return $journal->delete();
    }
}
