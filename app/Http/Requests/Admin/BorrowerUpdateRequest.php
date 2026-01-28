<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BorrowerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $borrowerId = $this->route('borrower')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['student', 'teacher'])],
            'class' => ['nullable', 'string', 'max:50'],
            'major' => ['nullable', 'string', 'max:100'],
            'id_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('borrowers', 'id_number')
                    ->whereNull('deleted_at')
                    ->ignore($borrowerId),
            ],
            'contact' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'blocked'])],
        ];
    }
}
