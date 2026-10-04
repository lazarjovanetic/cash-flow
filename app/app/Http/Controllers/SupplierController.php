<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\Ledger;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function show(Supplier $supplier)
    {
        $ledger = new Ledger;

        return view('supplier', ['ledger' => $ledger, 'cur' => $ledger->find($supplier->id)]);
    }

    public function store(Request $request)
    {
        $data = $this->validateModal($request, 'supplier', [
            'name' => ['required', 'string', 'max:255'],
            'pib' => ['nullable', 'string', 'max:32'],
            'term_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], ['name.required' => 'Unesite naziv dobavljača.']);

        $supplier = Supplier::create([
            'name' => trim($data['name']),
            'pib' => $data['pib'] ?? null,
            'term_days' => $data['term_days'] ?? config('dugovanja.podrazumevani_rok'),
        ]);

        return redirect()->route('suppliers.show', $supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('home');
    }
}
