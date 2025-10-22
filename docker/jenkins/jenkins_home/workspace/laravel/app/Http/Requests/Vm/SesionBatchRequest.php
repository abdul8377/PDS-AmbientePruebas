<?php

namespace App\Http\Requests\Vm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SesionBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'mode'         => ['required', Rule::in(['range','list'])],

            'hora_inicio'  => ['required','date_format:H:i'],
            'hora_fin'     => ['required','date_format:H:i','after:hora_inicio'],

            'fecha_inicio' => ['required_if:mode,range','date'],
            'fecha_fin'    => ['required_if:mode,range','date','after_or_equal:fecha_inicio'],
            'dias_semana'  => ['nullable','array'],
            'dias_semana.*'=> ['nullable'],

            'fechas'       => ['required_if:mode,list','array','min:1'],
            'fechas.*'     => ['date'],
        ];
    }

    public function messages(): array
    {
        return ['hora_fin.after' => 'La hora_fin debe ser mayor a hora_inicio.'];
    }
}
