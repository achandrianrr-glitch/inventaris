<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brandId = $this->route('brand')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('brands', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($brandId),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
