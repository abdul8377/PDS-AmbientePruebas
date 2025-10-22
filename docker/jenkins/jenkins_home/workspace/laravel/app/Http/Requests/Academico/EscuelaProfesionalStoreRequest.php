<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class EscuelaProfesionalStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'facultad_id' => ['required','integer','exists:facultades,id'],
            'codigo'      => ['required','string','max:255'],
            'nombre'      => ['required','string','max:255'],
        ];
    }
}
