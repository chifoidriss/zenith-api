<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PartnerController extends Controller
{
    public function index($type)
    {
        $partners = Partner::query();
        $q = request()->q;

        if ($type != 'all') {
            $partners = $partners->where(['type' => partnerType($type)]);
        }

        if ($q) {
            $partners->where(function ($query) use ($q) {
                $query->where('phone', 'LIKE', "%$q%")
                ->orWhere('first_name', 'LIKE', "%$q%")
                ->orWhere('last_name', 'LIKE', "%$q%");
            });
        }

        return $partners->latest()->paginate(20);
    }

    public function show($type, $id)
    {
        return Partner::with([
            'invoices',
            'defaultAccount',
        ])->where([
            // 'type' => partnerType($type),
            'id' => $id
        ])->firstOrFail();
    }

    public function store(Request $request, $type)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $partner = Partner::firstOrNew([
                'id' => $request->id,
                'type' => partnerType($type),
            ]);

            $partner->fill($request->only([
                'first_name',
                'last_name',
                'partner_type',
                'barcode',
                'title',
                'society_name',
                'post',
                'phone',
                'mobile',
                'email',
                'website',
                'birthday',
                'birthplace',
                'country_id',
                'nationality_id',
                'city',
                'genre',
                'identity_type',
                'identity_number',
                'identity_delivery_place',
                'identity_delivery_date',
                // 'identity_expiry_date',
                'default_account_id',
            ]));

            if(!$partner->default_account_id) {
                if($partner->type == 'CLIENT') {
                    $partner->default_account_id = 451;
                } elseif($partner->type == 'SUPPLIER') {
                    $partner->default_account_id = 430;
                } elseif($partner->type == 'SALARY') {
                    $partner->default_account_id = 483;
                }
            }

            if(!$partner->reference) {
                // $last = Partner::orderBy('id', 'DESC')->first();
                $last = Partner::selectRaw("MAX(CONVERT(SUBSTRING_INDEX(reference, '/', -1), UNSIGNED)) AS number")
                ->where(['type' => partnerType($type)])->first();

                $ref = substr(partnerType($type), 0, 3);
                $ref .= '/'.str_pad($last ? ($last->number+1) : 1, 4, '0', STR_PAD_LEFT);
                $partner->reference = $ref;
            }
            $partner->save();

            # All is good
            DB::commit();

            return $this->show($type, $partner->id);
        }  catch (Throwable $ex) {
            DB::rollBack();

            return response([
                'error' => 'Erreur interne du serveur',
                'message' => $ex,
            ], 500);

            throw $ex;
        }
    }

    public function destroy($type, $id)
    {
        $partner = Partner::findOrFail($id);
        return $partner->delete();
    }

    public function filterAccount($q)
    {
        $accounts = Partner::with('accountType')->whereBetween('code', $q)->get();
        // $accounts = ChartAccount::where('code', 'regex', '^'.$code)->get();
        return $accounts;
    }

    public function filter($type)
    {
        $q = request()->q ?? '';

        // if (strlen($q) < 3) {
        //     return [];
        // }

        $partners = Partner::query();
        if ($type) {
            $partners = $partners->where(['type' => partnerType($type)]);
        }

        $partners->where(function ($query) use ($q) {
            $query->where('phone', 'LIKE', "%$q%")
            ->orWhere('identity_number', 'LIKE', "%$q%")
            ->orWhere('first_name', 'LIKE', "%$q%")
            ->orWhere('last_name', 'LIKE', "%$q%");
        });

        return $partners->limit(10)->get();
    }
}
