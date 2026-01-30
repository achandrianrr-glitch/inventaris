<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DamageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'borrowing_id' => ['nullable', 'integer', 'exists:borrowings,id'],
            'damage_level' => ['required', Rule::in(['minor', 'moderate', 'heavy'])],
            'description' => ['required', 'string', 'max:2000'],
            'reported_date' => ['required', 'date'],
        ];
    }
}
