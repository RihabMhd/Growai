<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug'              => 'sometimes|string|max:60|unique:order_statuses,slug,' . optional($this->route('orderStatus'))->id,
            'name'              => 'sometimes|string|max:100',
            'whatsapp_message'  => 'nullable|string',
            'auto_send'         => 'sometimes|boolean',
            'templates'         => 'nullable|array',
            'templates.*'       => 'nullable|string',
        ];
    }
}