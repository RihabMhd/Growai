<?php
namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'                 => 'nullable|string',
            'role'                 => 'nullable|in:admin,agent,staff',
            'is_active'            => 'nullable|boolean',
            'quota'                => 'nullable|integer|min:0',
            'is_dispatch_active'   => 'nullable|boolean',
            'commission_trigger'   => 'nullable|string',
            'commission_amount'    => 'nullable|numeric|min:0',
            'commission_type'      => 'nullable|string|in:fixed,percent',
            'avatar'               => 'nullable|string',
            'product_ids'          => 'nullable|array',
        ];
    }
}