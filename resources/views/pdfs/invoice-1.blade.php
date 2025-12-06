@extends('invoices.layout')

@section('title', $title)

@section('content')
<div class="details mt-4">
    <h5 class="text-muted">{{ $title }}</h5>
    <div class="row">
        <div class="col-xs-6">
            <div class="fw-bold">Date de facturation :</div>
            <div class="">{{ $invoice->billing_date->format('d/m/Y') }}</div>
        </div>
        <div class="col-xs-6">
            <div class="fw-bold">Date d'échéance :</div>
            <div class="">{{ $invoice->due_date->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

<div class="mt-4">
    <table class="table table-bordered table-striped-">
        <thead>
            <tr class="active text-nowrap">
                <th width="100%">Description</th>
                <th class="text-right" width="5px">Quantité</th>
                <th class="text-right">Prix U.</th>
                {{-- <th>Taxes</th> --}}
                <th class="text-right">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($invoice->items as $item)
            <tr>
                <td class="text-wrap">{{ $item->label }}</td>
                <td class="text-right text-nowrap">
                    {{ $item->qty }}
                    {{ $item->unit->code }}
                </td>
                <td class="text-right text-nowrap">{{ getPrice($item->price, $invoice->devise->devise) }}</td>
                {{-- <td class="text-wrap">{{ $item->taxes->implode('name', ', ') }}</td> --}}
                <td class="text-right text-nowrap">{{ getPrice($item->subtotal, $invoice->devise->devise) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="row">
    <div class="col-xs-6"></div>
    <div class="col-xs-6">
        <table class="table table-bordered">
            <tbody>
                {{-- <tr>
                    <th>Sous-total</th>
                    <td class="text-right">{{ getPrice($invoice->subtotal) }}</td>
                </tr> --}}
                <tr>
                    <th>Total à payer</th>
                    <td class="text-right">
                        {{ getPrice($invoice->total, $invoice->devise->devise) }}
                    </td>
                </tr>
                {{-- @foreach ($invoice->taxe_line as $item)
                <tr>
                    <th>{{ $taxe->name }}</th>
                    <td class="text-right"></td>
                </tr>
                @endforeach --}}
                @foreach ($invoice->payments as $item)
                <tr>
                    <th>
                        Payé le <i class="text-italic">{{ $item->payment_date->format('d/m/Y') }}</i>
                    </th>
                    <td class="text-right">{{ getPrice($item->amount, $item->devise->devise) }}</td>
                </tr>
                @endforeach
                <tr>
                    <th>Montant restant</th>
                    <td class="text-right">
                        {{ getPrice($invoice->due_amount, $invoice->devise->devise) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- <div class="">
    <div class="">
        Merci d'utiliser la communication suivante pour votre paiement: <b>{{ $invoice->reference }}</b>
    </div>
</div> --}}
@endsection
