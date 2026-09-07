<?php

namespace SalsabilEnnaiem\PvModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'contenu' => ['nullable', 'array'],
            'template_data' => ['nullable', 'array'],
            'new_template_config' => ['nullable', 'array'],
            'template_choice' => ['nullable', 'in:current,new'],
            'receiver_placements' => ['nullable', 'array'],
            'receivers' => ['nullable', 'array'],
            'signature_deadline' => ['nullable', 'date'],
        ];
    }
}