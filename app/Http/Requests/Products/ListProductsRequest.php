<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

final class ListProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'       => 'nullable|in:active,draft,archived',
            'source_type'  => 'nullable|in:manual,shopify',
            'vendor'       => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'search'       => 'nullable|string|max:255',
            'tag'          => 'nullable|string|max:255',
            'sort_by'      => 'nullable|in:created_at,updated_at,title,price,stock,status,vendor',
            'sort_order'   => 'nullable|in:asc,desc',
            'per_page'     => 'nullable|integer|min:1|max:100',
            'min_price'    => 'nullable|numeric|min:0',
            'stock_status' => 'nullable|in:in_stock,out_of_stock,low_stock',
        ];
    }
}