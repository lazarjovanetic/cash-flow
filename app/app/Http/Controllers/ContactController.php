<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request, Supplier $supplier)
    {
        $data = $this->validateModal($request, 'contact', [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
        ], ['name.required' => 'Unesite ime.']);

        $supplier->contacts()->create(['name' => trim($data['name'])] + $data);

        return back();
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return back();
    }
}
