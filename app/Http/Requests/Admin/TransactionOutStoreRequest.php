<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionOutStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                'integer',
                // ✅ item harus ada, tidak trashed, dan status active
                Rule::exists('items', 'id')->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->where('status', 'active');
                }),
            ],
            'qty' => ['required', 'integer', 'min:1'],
            'to_location' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.exists' => 'Barang tidak valid',
        ];
    }
}
