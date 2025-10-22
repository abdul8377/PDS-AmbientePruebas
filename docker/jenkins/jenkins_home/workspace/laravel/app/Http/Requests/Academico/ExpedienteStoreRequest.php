<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpedienteStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'   => ['required','integer','exists:users,id'],
            'ep_sede_id'=> ['required','integer','exists:ep_sede,id'],

            // estudiante puede venir sin código y correo; staff también.
            'codigo_estudiante'    => ['nullable','string','max:255'],
            'grupo'                => ['nullable','string','max:255'],
            'correo_institucional' => ['nullable','email','max:255'],

            // por defecto ESTUDIANTE; para staff usar los endpoints específicos abajo
            'rol' => ['nullable', Rule::in(['ESTUDIANTE','COORDINADOR','ENCARGADO'])],
        ];
    }
}
