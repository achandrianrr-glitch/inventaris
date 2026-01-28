<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BorrowerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['student', 'teacher'])],

            // Optional tapi wajib rapi
            'class' => ['nullable', 'string', 'max:50'],
            'major' => ['nullable', 'string', 'max:100'],

            // id_number wajib unik jika diisi (NIS/NIP)
            'id_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('borrowers', 'id_number')->whereNull('deleted_at'),
            ],

            'contact' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'blocked'])],
        ];
    }

    public function messages(): array
    {
        return [
            'id_number.unique' => 'NIS/NIP sudah dipakai peminjam lain.',
        ];
    }
}
