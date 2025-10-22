<?php

namespace App\Http\Requests\Vm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'periodo_id'  => ['required','integer','exists:periodos_academicos,id'],
            'target_type' => ['required', Rule::in(['ep_sede','sede','facultad'])],
            'target_id'   => ['required','integer'],

            'codigo'      => ['nullable','string','max:255','unique:vm_eventos,codigo'],
            'titulo'      => ['required','string','max:255'],

            'fecha'       => ['required','date'],
            'hora_inicio' => ['required','date_format:H:i'],
            'hora_fin'    => ['required','date_format:H:i','after:hora_inicio'],

            'requiere_inscripcion' => ['sometimes','boolean'],
            'cupo_maximo'          => ['nullable','integer','min:1'],
        ];
    }
}
