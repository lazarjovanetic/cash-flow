<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    /**
     * Validira formu iz modala. Pri grešci vraća korisnika nazad sa ponovo otvorenim
     * istim modalom (polje "_modal"), unetim vrednostima i porukom na srpskom.
     */
    protected function validateModal(Request $request, string $modal, array $rules, array $messages = [], ?callable $after = null): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($after) {
            $validator->after(function ($v) use ($after, $request) {
                foreach ((array) $after($request->all()) as $field => $msg) {
                    $v->errors()->add($field, $msg);
                }
            });
        }

        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo(url()->previous());
        }

        return $validator->validated();
    }
}
