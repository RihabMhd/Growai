<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


final class StoreOrderShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Base rules
        $base = [
            'delivery_company_id' => 'required|exists:delivery_companies,id',
            'city'                => 'required|string|max:255',
            'client_name'         => 'required|string|max:255',
            'phone'               => 'required|string|max:50',
            'address'             => 'required|string|max:1000',
            'total'               => 'required|numeric|min:0',
            'note'                => 'nullable|string',
        ];

        $companyId = (int) ($this->input('delivery_company_id') ?? 0);

        // Ensure selected company is active
        // Active check handled in withValidator() to avoid custom rule dependencies
        $base['delivery_company_id'] = 'required|exists:delivery_companies,id';

        // Dynamic carrier-specific rules
        $ameexRules = [
            'api_id'        => 'required|string|max:255',
            'delivery_type'=> 'required|string|max:50',
            'openable'      => 'required|boolean',
            'test_product'  => 'required|boolean',
            'fragile'       => 'required|boolean',
            'product'       => 'required|string|max:255',
            'exchange'      => 'required|boolean',
        ];

        // Identify AMEEX by slug
        $rules = $base;

        $company = \App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel::query()->find($companyId);
        $isAmeex = $company && strcasecmp((string) $company->slug, 'ameex') === 0;

        if ($isAmeex) {
            $rules = array_merge($rules, $ameexRules);
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $companyId = (int) ($this->input('delivery_company_id') ?? 0);
            $company = \App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel::query()->find($companyId);

            if (! $company || ! (bool) $company->is_active) {
                $validator->errors()->add('delivery_company_id', 'The selected delivery company is not active.');
            }

            if ($company && strcasecmp((string) $company->slug, 'ameex') === 0) {
                // no-op; AMEEX validation already applied via rules()
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // Normalize booleans for checkbox-like inputs
        $boolFields = ['openable', 'test_product', 'fragile', 'exchange'];
        foreach ($boolFields as $f) {
            if ($this->has($f)) {
                $this->merge([$f => filter_var($this->input($f), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
            }
        }
    }
}

