<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int)$this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
                function ($attr, $val, $fail) {
                    if (!str_ends_with(strtolower($val), '@gmail.com')) {
                        $fail('Email harus menggunakan @gmail.com');
                    }
                }
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
