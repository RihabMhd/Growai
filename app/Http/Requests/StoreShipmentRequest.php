<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'delivery_company_id' => 'required|exists:delivery_companies,id',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:5',
            'cod_amount' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric',
            'dimensions.width' => 'nullable|numeric',
            'dimensions.height' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'La commande est requise.',
            'order_id.exists' => 'La commande spécifiée n\'existe pas.',
            'delivery_company_id.required' => 'Le transporteur est requis.',
            'delivery_company_id.exists' => 'Le transporteur spécifié n\'existe pas.',
        ];
    }
}
