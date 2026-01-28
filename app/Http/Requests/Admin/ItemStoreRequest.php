<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],

            'specification' => ['nullable', 'string', 'max:4000'],
            'purchase_year' => ['nullable', 'integer', 'min:1990', 'max:' . date('Y')],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],

            'stock_total' => ['required', 'integer', 'min:0'],
            'condition' => ['required', Rule::in(['good', 'minor', 'heavy'])],
            'status' => ['required', Rule::in(['active', 'service', 'inactive'])],
        ];
    }
}
