<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class AsignarResponsableRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'       => ['required','integer','exists:users,id'],
            'vigente_desde' => ['nullable','date'],
            'vigente_hasta' => ['nullable','date','after_or_equal:vigente_desde'],
        ];
    }
}
