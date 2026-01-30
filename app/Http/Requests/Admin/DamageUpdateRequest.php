<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DamageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
            'solution' => ['nullable', 'string', 'max:2000'],
            'completion_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if ($this->input('status') === 'completed') {
                if (!$this->input('solution')) $v->errors()->add('solution', 'Solusi wajib diisi jika status completed.');
                if (!$this->input('completion_date')) $v->errors()->add('completion_date', 'Tanggal selesai wajib jika status completed.');
            }
        });
    }
}
