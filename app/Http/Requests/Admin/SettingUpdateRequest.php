<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'code_format' => ['required', 'string', 'max:100'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'notification_wa' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'school_name.required' => 'SMKN 9 MALANG.',
            'city.required' => 'Malang',
            'code_format.required' => 'Format kode wajib diisi.',
            'notification_email.email' => 'inventaryy@gmail.com',
        ];
    }
}
