<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    public function index()
    {
        return Absence::with('partner')->paginate(100);
    }

    public function show($id)
    {
        return Absence::with('partner')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id'=>'required|exists:partners,id',
            'reason'=>'nullable|string',
            'start_date'=>'required|date',
            'amount'=>'required|integer',
            'justified'=>'required|boolean',
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

        $exist = Absence::where('partner_id', $request->partner_id)
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
                    'start_date' => ['Une absence existe déjà à cette date.']
                ]
            ], 422);
        }

        $absence = Absence::firstOrNew([
            'id' => $request->input('id')
        ]);

        $absence->fill($request->only([
            'partner_id',
            'reason',
            'start_date',
            'amount',
            'justified',
        ]));

        $absence->end_date = $end_date;
        $absence->save();

        return $this->show($absence->id);
    }

    public function destroy($id)
    {
        $absence = Absence::findOrFail($id);
        return $absence->delete();
    }
}
