<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\Fmt;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateModal($request, 'invoice', [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'number' => ['nullable', 'string', 'max:64'],
            'issued_on' => ['required', 'date'],
            'amount' => ['required', 'string'],
            'term_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], [
            'supplier_id.*' => 'Izaberite dobavljača.',
            'issued_on.*' => 'Unesite datum fakture.',
            'amount.*' => 'Unesite ispravan iznos.',
        ], fn ($data) => ($cents = Fmt::parseAmount($data['amount'] ?? null)) && $cents > 0 ? null : ['amount' => 'Unesite ispravan iznos.']);

        Invoice::create([
            'supplier_id' => $data['supplier_id'],
            'number' => trim($data['number'] ?? '') ?: '—',
            'issued_on' => $data['issued_on'],
            'term_days' => $data['term_days'] ?? config('dugovanja.podrazumevani_rok'),
            'amount' => Fmt::parseAmount($data['amount']) / 100,
        ]);

        return back();
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return back();
    }
}
