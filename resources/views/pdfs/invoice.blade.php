@extends('pdfs.layout')
@section('title', 'FACTURE')

@section('content')
<div class="row">
    <div class="col-xs-8">
        <h4 class="text-primary">{{ $society->name }}</h4>
        <p>
            {{ $society->address_name }} <br>
            Email: {{ $society->email }}<br>
            Tél : {{ $society->phone }}
        </p>
    </div>
    <div class="col-xs-4">
        <div class="">
            <h3 class="text-primary">FACTURE</h3>
            <h6>Numéro: {{ $data->reference }}</h6>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-xs-5">
        <div class="bg-primary text-white fw-bold p-2">
            FACTURÉ À
        </div>
        <h5 class="mt-2">
            {{ $data->partner->full_name }}
        </h5>
        @if ($data->partner->phone)
        <div>Tél: {{ $data->partner->phone }}</div>
        @endif
        @if ($data->partner->email)
        <div>Email: {{ $data->partner->email }}</div>
        @endif
    </div>

    <div class="col-xs-2"></div>

    <div class="col-xs-5">
        <h5>
            {{-- Code promo: {{ $order->partner->id }} --}}
        </h5>
    </div>
</div>

<div class="details mt-3">
    <h5 class="text-muted"></h5>
    <div class="row">
        <div class="col-xs-6">
            <div class="fw-bold">Date de facturation :</div>
            <div class="">{{ formatDate($data->billing_date) }}</div>
        </div>
        <div class="col-xs-6">
            <div class="fw-bold">Date d'échéance :</div>
            <div class="">{{ formatDate($data->due_date) }}</div>
        </div>
    </div>
</div>

<div class="mt-3">
    <table class="table table-bordered- table-xs table-striped-">
        <thead>
            <tr class="bg-primary text-white text-nowrap">
                <th class="py-2" width="100%">Designation</th>
                <th class="text-right py-2" width="5px">Quantité</th>
                <th class="text-right py-2">Prix</th>
                <th class="text-right py-2">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($data->items as $item)
            <tr>
                <td class="text-wrap py-2">{{ $item->label }}</td>
                <td class="text-right text-nowrap py-2">
                    {{ $item->qty }}
                    {{ $item->unit->name }}
                </td>
                <td class="text-right text-nowrap py-2">
                    {{ getPrice($item->price, $data->devise->devise) }}
                </td>
                <td class="text-right text-nowrap py-2">
                    {{ getPrice($item->subtotal, $data->devise->devise) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="">
                <td class="border-0 border-top py-2"></td>
                <td class="active py-2" colspan="2">Total à payer</td>
                <th class="text-right active text-nowrap py-2">
                    {{ getPrice($data->total, $data->devise->devise) }}
                </th>
            </tr>
            @foreach ($data->payments as $item)
            <tr class="">
                <td class="border-0 py-2"></td>
                <td class="active py-2" colspan="2">
                    Payé le <i class="text-italic">{{ formatDate($item->payment_date) }}</i>
                </td>
                <th class="text-right active text-nowrap py-2">
                    {{ getPrice($item->amount, $item->devise->devise) }}
                </th>
            </tr>
            @endforeach
            <tr class="">
                <td class="border-0 py-2"></td>
                <td class="active py-2" colspan="2">Montant restant</td>
                <th class="text-right active text-nowrap py-2">
                    {{ getPrice($data->due_amount, $data->devise->devise) }}
                </th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="">
    <div class="">
        Merci d'utiliser la référence suivante pour votre paiement:
        <b>{{ $data->reference }}</b>
    </div>
    {{-- <div class="">
        @foreach($data->unit as $u)
        <span class="px-3 py-2 rounded bg-light me-2">
            {{ $u['total'] }} <b>{{ $u['unit'] }}</b>
        </span>
        @endforeach
    </div> --}}
</div>
@endsection
