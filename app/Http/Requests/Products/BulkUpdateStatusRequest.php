<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

final class BulkUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'required|integer|exists:products,id',
            'status' => 'required|in:active,draft,archived',
        ];
    }
}