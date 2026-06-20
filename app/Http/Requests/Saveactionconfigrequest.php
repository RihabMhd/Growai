<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

final class SaveActionConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // team/auth scoping is enforced in the handler via team_id
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