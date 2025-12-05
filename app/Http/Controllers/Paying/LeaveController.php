<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index()
    {
        return Leave::with('partner')->paginate(100);
    }

    public function show($id)
    {
        return Leave::with('partner')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id'=>'required|exists:partners,id',
            'observation'=>'nullable|string',
            'leaving_type_id'=>'required|exists:leaving_types,id',
            'start_date'=>'required|date',
            'amount'=>'required|numeric',
            'payed'=>'required|boolean',
        ]);

        $start_date = Carbon::parse($request->start_date);
        $end_date = Carbon::parse($request->start_date)->addDays($request->longtime);

        if ($start_date < now()->firstOfMonth()) {
            return response()->json([
                'message' => 'Opération impossible.',
                'errors' => [
                    'start_date' => ['Date incorrecte.']
                ]
            ], 422);
        }

        $exist = Leave::where('partner_id', $request->partner_id)
        ->where(function($query) use ($start_date, $end_date) {
            $query->whereBetween('start_date', [$start_date, $end_date])
            ->orWhereBetween('end_date', [$start_date, $end_date])
            ->orWhere(function ($query) use ($start_date, $end_date) {
                $query->where('start_date', '<', $start_date)
                ->where('end_date', '>', $end_date);
            });
        })->first();

        if ($exist) {
            return response()->json([
                'message' => 'Opération impossible.',
                'errors' => [
                    'start_date' => ['Un congé existe déjà à cette date.']
                ]
            ], 422);
        }

        $leave = Leave::firstOrNew([
            'id' => $request->input('id')
        ]);

        $leave->fill($request->only([
            'partner_id',
            'leaving_type_id',
            'observation',
            'start_date',
            'amount',
            'payed',
        ]));
        $leave->end_date = Carbon::parse($request->start_date)->addDays($request->longtime);
        $leave->save();

        return $this->show($leave->id);
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        return $leave->delete();
    }
}
