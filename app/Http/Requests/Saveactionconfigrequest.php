<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

final class SaveActionConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        // team scoping enforced via team id
        return true;
    }

    public function rules(): array
    {
        return [
            'prefilled' => ['sometimes', 'array'],
            'hidden' => ['sometimes', 'array'],
            'hidden.*' => ['boolean'],
            'credentials' => ['sometimes', 'array'],
            'auto_create_enabled' => ['sometimes', 'boolean'],
        ];
    }
}