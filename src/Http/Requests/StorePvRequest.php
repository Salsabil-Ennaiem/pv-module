<?php

namespace SalsabilEnnaiem\PvModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'contenu' => ['nullable', 'array'],
            'template_data' => ['nullable', 'array'],
            'type' => ['nullable', 'string', 'max:50'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer'],
            'receivers' => ['nullable', 'array'],
            'signature_deadline' => ['nullable', 'date'],
        ];
    }
}