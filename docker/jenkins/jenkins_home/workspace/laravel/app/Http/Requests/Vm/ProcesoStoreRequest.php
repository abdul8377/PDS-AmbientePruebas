<?php

namespace App\Http\Requests\Vm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcesoStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tipo = $this->input('tipo_registro');

        return [
            'nombre' => ['required','string','max:255'],
            'descripcion' => ['nullable','string'],
            'tipo_registro' => ['required', Rule::in(['HORAS','ASISTENCIA','EVALUACION','MIXTO'])],
            'horas_asignadas' => [
                'nullable','integer','min:0','max:32767',
                Rule::requiredIf(in_array($tipo, ['HORAS','MIXTO'], true)),
            ],
            'nota_minima' => [
                'nullable','integer','min:0','max:100',
                Rule::requiredIf(in_array($tipo, ['EVALUACION','MIXTO'], true)),
            ],
            'requiere_asistencia' => ['sometimes','boolean'],
            'orden' => ['nullable','integer','min:1','max:65535'],
        ];
    }
}
