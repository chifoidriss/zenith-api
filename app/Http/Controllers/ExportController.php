<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Leave;
use App\Models\Salary;
use App\Models\Absence;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Society;
use App\Models\Transfer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function printToPDF($type, $id) {
        if ($type == 'invoice') {
            $data = Invoice::findOrFail($id);
        } elseif($type == 'transfer') {
            $data = Transfer::findOrFail($id);
        } elseif($type == 'salary') {
            $data = Salary::findOrFail($id);
        } elseif($type == 'loan') {
            $data = Loan::findOrFail($id);
        } elseif($type == 'absence') {
            $data = Absence::findOrFail($id);
        } elseif($type == 'leave') {
            $data = Leave::findOrFail($id);
        } elseif($type == 'payment') {
            $data = Payment::findOrFail($id);
        }

        $society = Society::first();
        $fileName = strtoupper($data->reference)."-".time().".pdf";
        $path = 'documents/'.Str::plural($type).'/'.$fileName;

        $pdf = Pdf::loadView("pdfs.$type", compact("data", 'society'))->save($path, 'public');

        return Storage::download($path);

        // return ['url' => asset('storage/'.$path)];
    }
}
