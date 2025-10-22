<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class EpSedeStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'escuela_profesional_id' => ['required','integer','exists:escuelas_profesionales,id'],
            'sede_id'                => ['required','integer','exists:sedes,id'],
            'vigente_desde'          => ['nullable','date'],
            'vigente_hasta'          => ['nullable','date','after_or_equal:vigente_desde'],
        ];
    }
}
