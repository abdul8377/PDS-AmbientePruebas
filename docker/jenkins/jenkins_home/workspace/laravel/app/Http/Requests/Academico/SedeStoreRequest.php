<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class SedeStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'          => ['required', 'string', 'max:255'],
            'es_principal'    => ['sometimes', 'boolean'],
            'esta_suspendida' => ['sometimes', 'boolean'],
        ];
    }
}
