<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Support\Str;
use App\Models\ChartAccount;
use Illuminate\Http\Request;

class ChartAccountController extends Controller
{

    public function index()
    {
        $code = request()->code;

        $accounts = ChartAccount::with('accountType');
        if($code) {
            $start = Str::padRight(intval($code), 6, '0');
            $end = Str::padRight(intval($code) + 1, 6, '0');

            $accounts = $accounts->whereBetween('code', [$start, $end]);
        }

        return $accounts->paginate(20);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'=>'required|numeric',
            'name'=>'required|string',
            'allow_reconciliation'=>'nullable|boolean',
            'deprecated'=>'nullable|boolean',
            'account_type_id'=>'required|numeric',
        ]);

        $chart_account = ChartAccount::firstOrNew([
            'id' => $request->input('id')
        ]);
        $chart_account->fill($request->only([
            'code',
            'name',
            'deprecated',
            'allow_reconciliation',
            'account_type_id',
        ]));
        $chart_account->save();

        return $this->show($chart_account->id);
    }

    public function show($id)
    {
        return ChartAccount::with('accountType')->findOrFail($id);
    }

    public function destroy($id)
    {
        $chart_account = ChartAccount::findOrFail($id);
        return $chart_account->delete();
    }

    public function depreciate($id,Request $request)
    {
        $chart_account = ChartAccount::findOrFail($id);

        if ($request->method == 'deprecated') {
            $chart_account->deprecated = !$chart_account->deprecated;
            $chart_account->save();

            return $chart_account->deprecated;
        } elseif($request->method == 'allow') {
            $chart_account->allow_reconciliation = !$chart_account->allow_reconciliation;
            $chart_account->save();

            return $chart_account->allow_reconciliation;
        }
    }

    public function indexTypeAccount()
    {
        $data = AccountType::all();
        return $data;
    }

    public function filterAccount($code)
    {
        $start = Str::padRight(intval($code), 6, '0');
        $end = Str::padRight(intval($code) + 1, 6, '0');

        $accounts = ChartAccount::with('accountType')->whereBetween('code', [$start, $end])->get();
        // $accounts = ChartAccount::where('code', 'regex', '^'.$code)->get();
        return $accounts;
    }
}
