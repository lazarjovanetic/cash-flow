<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Fmt;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $data = $this->validateModal($request, 'payment', [
            'paid_on' => ['nullable', 'date'],
            'amount' => ['required', 'string'],
        ], ['amount.*' => 'Unesite ispravan iznos.', 'paid_on.*' => 'Unesite ispravan datum.'],
            fn ($data) => ($cents = Fmt::parseAmount($data['amount'] ?? null)) && $cents > 0 ? null : ['amount' => 'Unesite ispravan iznos.']);

        $invoice->payments()->create([
            'paid_on' => $data['paid_on'] ?? today(),
            'amount' => Fmt::parseAmount($data['amount']) / 100,
        ]);

        return back();
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return back();
    }
}
