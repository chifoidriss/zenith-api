@extends('pdfs.layout')

@section('title', 'BULLETIN DE PAIE')

@section('content')
<table class="table border-0 table-xs table-borderless mb-0">
    <tr class="border-0 text-nowrap">
        <th class="border-0 text-nowrap">
            <h4 class="mb-0 text-primary-">
                {{ $society->name }}
            </h4>
            <h5 class="mb-0 text-primary-">
                Numéro contribuable: <u>{{ $society->registre }}</u>
            </h5>
            <h5 class="mb-0 text-primary-">
                Paie du : <u>{{ $data->start_date }}</u>
                Au : <u>{{ $data->end_date }}</u>
            </h5>
            <h5 class="mb-0 text-primary-">
                Employé(e) : <u>{{ $data->partner->full_name }}</u>
            </h5>
            <h5 class="mb-0 text-primary-">
                Fonction : <u>{{ $data->contract->post->name }}</u>
            </h5>
        </th>
        <th class="border-0 text-end text-nowrap">
            <h3 class="mb-0 text-primary">
                BULLETIN DE PAIE
            </h3>
            <h4>
                Numéro: <b>{{ $data->reference }}</b>
            </h4>
        </th>
    </tr>
</table>

<div class="mt-3">
    <table class="table table-borderless mb-0">
        <thead class="table-primary-">
            <tr>
                <th colspan="2">TAUX DE RÉMUNÉRATION</th>
                <!-- <th></th> -->
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Mensuel Journalier Horaire</td>
                <td class="text-end">{{ getPrice($data->salary_total) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="table table-borderless mb-0">
        <thead class="table-primary-">
            <tr>
                <th colspan="2">PRIMES</th>
                <!-- <th></th> -->
            </tr>
        </thead>
        <tbody>
            @foreach ($data->bonuses as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td class="text-end">{{ getPrice($item->pivot->value) }}</td>
            </tr>
            @endforeach

            <tr>
                <td>Congés payés</td>
                <td class="text-end">{{ getPrice($data->leave_total) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="table table-borderless mb-0">
        <thead class="table-primary-">
            <tr>
                <th colspan="2">INDEMNITÉS</th>
                <!-- <th></th> -->
            </tr>
        </thead>
        <tbody>
            @foreach ($data->indemnities as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td class="text-end">{{ getPrice($item->pivot->value) }}</td>
            </tr>
            @endforeach

            <tr>
                <th>MONTANT RÉMUNÉRATION BRUTE</th>
                <th class="text-end">{{ getPrice($data->total_salary) }}</th>
            </tr>
        </tbody>
    </table>

    <table class="table table-borderless mb-0">
        <thead class="table-primary-">
            <tr>
                <th colspan="2">IMPÔTS</th>
                <!-- <th></th> -->
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>IRPP</td>
                <td class="text-end">{{ getPrice($data->tax_irpp_total) }}</td>
            </tr>
            <tr>
                <td>CAC</td>
                <td class="text-end">{{ getPrice($data->tax_cac_total) }}</td>
            </tr>
            <tr>
                <td>CFC</td>
                <td class="text-end">{{ getPrice($data->tax_cfc_total) }}</td>
            </tr>
            <tr>
                <td>CRTV</td>
                <td class="text-end">{{ getPrice($data->tax_crtv_total) }}</td>
            </tr>
            <tr>
                <th>TOTAL IMPÔTS</th>
                <th class="text-end">{{ getPrice($data->total_tax) }}</th>
            </tr>
        </tbody>
    </table>

    <table class="table table-borderless mb-0">
        <thead class="table-primary-">
            <tr>
                <th colspan="2">RETENUES</th>
                <!-- <th></th> -->
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Acompte sur salaire</td>
                <td class="text-end">{{ getPrice($data->salary_advance) }}</td>
            </tr>
            <tr>
                <td>Prêts</td>
                <td class="text-end">{{ getPrice($data->salary_loan) }}</td>
            </tr>
            <tr>
                <td>Saisie et opposition</td>
                <td class="text-end">{{ getPrice($data->absence_total) }}</td>
            </tr>
            <tr>
                <td>PVID</td>
                <td class="text-end">{{ getPrice($data->pvid_total) }}</td>
            </tr>
            <tr>
                <td>Taxe communale</td>
                <td class="text-end">{{ getPrice($data->tax_municipal_total) }}</td>
            </tr>
            <tr>
                <td>Retenue syndicale</td>
                <td class="text-end">{{ getPrice($data->syndical_total) }}</td>
            </tr>
        </tbody>
        <tfoot class="table-primary-">
            <tr>
                <th>TOTAL RETENUES</th>
                <th class="text-end">{{ getPrice($data->total_retenu) }}</th>
            </tr>
            <tr>
                <th>NET À PAYER</th>
                <th class="text-end">{{ getPrice($data->total_salary_net) }}</th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="text-end mt-2">
    {{-- Fait à <u><b>{{ $data->created_at }}</b></u> --}}
    Fait à <b>.........................................</b>
    Le     <b>.........................................</b>
    {{-- Le <u><b>{{ formatDate($data->created_at) }}</b></u> --}}
</div>

<div class="mt-4">
    <table class="table table-borderless border-0">
        <thead class="border-0">
            <tr class="border-0">
                <th class="border-0">Visa employé(e)</th>
                <th class="text-end border-0">Visa employeur</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
