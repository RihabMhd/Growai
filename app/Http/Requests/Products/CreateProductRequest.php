<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'status'       => 'nullable|in:active,draft,archived',
            'source_type'  => 'nullable|in:manual,shopify',
            'vendor'       => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'handle'       => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'image'        => 'nullable|string|max:500',
            'images'       => 'nullable|array',
            'images.*'     => 'nullable|string|max:500',
            'cost'         => 'nullable|numeric|min:0',
            'tags'         => 'nullable',        // string or array — normalized in ProductData::fromArray
            'tags_string'  => 'nullable|string',
            'variants'     => 'nullable',        // JSON string or array — normalized in ProductData::fromArray
        ];
    }
}