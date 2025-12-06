@extends('pdfs.layout')

@section('title', 'TRANSFERT')

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
    <div class="col-xs-5">
        <div class="">
            <h3 class="text-primary">TRANSFERT</h3>
            <h6>Numéro: {{ $data->reference }}</h6>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-xs-5">
        <div class="bg-primary text-white fw-bold p-2">
            Magasin d'origine
        </div>
        <h5 class="mt-2">
            {{ $data->origin->name }}
        </h5>
    </div>

    <div class="col-xs-2"></div>

    <div class="col-xs-5">
        <div class="">
            <div class="fw-bold">Date d'opération :</div>
            <h5 class="">{{ formatDate($data->billing_date) }}</h5>
        </div>
        <div class="">
            <div class="fw-bold">Magasin destination :</div>
            {{-- <div class="">{{ formatDate($data->due_date) }}</div> --}}
            <h5>{{ $data->destination->name }}</h5>
        </div>
    </div>
</div>

<div class="mt-3">
    <table class="table table-xs-">
        <thead>
            <tr class="bg-primary text-white text-nowrap">
                <th class="py-2" width="100%">Designation</th>
                <th class="text-right- py-2" width="5px">Quantité</th>
                {{-- <th class="text-right py-2">Prix</th>
                <th class="text-right py-2">Total</th> --}}
            </tr>
        </thead>

        <tbody>
            @foreach ($data->items as $item)
            <tr>
                <td class="text-wrap py-2">{{ $item->article->name }}</td>
                <td class="text-right- text-nowrap py-2">
                    {{ $item->qty }} {{ $item->unit->name }}
                </td>
                {{-- <td class="text-right text-nowrap py-2">
                    {{ getPrice($item->price, $data->devise->devise) }}
                </td>
                <td class="text-right text-nowrap py-2">
                    {{ getPrice($item->subtotal, $data->devise->devise) }}
                </td> --}}
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="text-end mt-5">
    Fait à <b>.........................................</b>
    Le     <b>.........................................</b>
</div>
<div class="mt-5">
    <table class="table table-borderless border-0">
        <thead class="border-0">
            <tr class="border-0">
                <th class="border-0">Visa responsable magasin d'origine</th>
                <th class="text-end border-0">Visa responsable magasin destination</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
