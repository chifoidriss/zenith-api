<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return PaymentMethod::with('cashAccount')->paginate(100);
    }

    public function show($id)
    {
        return PaymentMethod::with('cashAccount')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
        ]);

        $method = PaymentMethod::firstOrNew([
            'id' => $request->input('id')
        ]);

        $method->fill($request->only([
            'name',
            'description',
            'cash_account_id',
        ]));
        $method->save();

        return $this->show($method->id);
    }

    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        return $method->delete();
    }
}
