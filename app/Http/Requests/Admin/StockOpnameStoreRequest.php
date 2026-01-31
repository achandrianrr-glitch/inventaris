<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StockOpnameStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'opname_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.physical_stock' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $lines = $this->input('lines', []);
            $ids = array_map(fn($x) => (int)($x['item_id'] ?? 0), $lines);
            $unique = array_unique($ids);

            if (count($ids) !== count($unique)) {
                $v->errors()->add('lines', 'Item duplikat dalam input opname.');
            }
        });
    }
}
