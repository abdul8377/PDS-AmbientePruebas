<?php

namespace App\Http\Requests\Vm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProyectoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza `tipo` a mayúsculas antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('tipo')) {
            $this->merge([
                'tipo' => strtoupper((string) $this->input('tipo')),
            ]);
        }
    }

    public function rules(): array
    {
        $tipo = (string) $this->input('tipo');

        // Reglas para `nivel` según `tipo`
        $nivelRules = ['bail'];
        if (in_array($tipo, ['VINCULADO', 'PROYECTO'], true)) {
            // Requerido y único por (ep_sede_id, periodo_id)
            $nivelRules[] = 'required';
            $nivelRules[] = 'integer';
            $nivelRules[] = 'between:1,10';
            $nivelRules[] = Rule::unique('vm_proyectos', 'nivel')
                ->where(fn ($q) => $q
                    ->where('ep_sede_id', $this->input('ep_sede_id'))
                    ->where('periodo_id', $this->input('periodo_id'))
                );
        } else {
            // LIBRE: no debe venir `nivel`
            $nivelRules[] = 'prohibited';
        }

        return [
            'ep_sede_id'  => ['required','integer','exists:ep_sede,id'],
            'periodo_id'  => ['required','integer','exists:periodos_academicos,id'],

            // Único global si lo envían; si no, se genera en el controller
            'codigo'      => ['nullable','string','max:255','unique:vm_proyectos,codigo'],

            'titulo'      => ['required','string','max:255'],
            'descripcion' => ['nullable','string'],
            'tipo'        => ['required', Rule::in(['VINCULADO','LIBRE'])],
            'modalidad'   => ['required', Rule::in(['PRESENCIAL','VIRTUAL','MIXTA'])],

            // Condicional según `tipo`
            'nivel'       => $nivelRules,

            'horas_planificadas'         => ['required','integer','min:1','max:32767'],
            'horas_minimas_participante' => ['nullable','integer','min:0','max:32767'],
        ];
    }

    public function messages(): array
    {
        return [
            'nivel.required'   => 'El nivel es obligatorio para proyectos vinculados.',
            'nivel.prohibited' => 'Los proyectos de tipo LIBRE no deben incluir nivel.',
        ];
    }
}
