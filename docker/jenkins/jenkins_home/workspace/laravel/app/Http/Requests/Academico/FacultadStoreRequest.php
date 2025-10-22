<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class FacultadStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'codigo' => ['required','string','max:255'],
            'nombre' => ['required','string','max:255'],
        ];
    }
}
