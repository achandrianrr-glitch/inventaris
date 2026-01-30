<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BorrowingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'borrower_id' => ['required', 'integer', 'exists:borrowers,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'qty' => ['required', 'integer', 'min:1'],

            'borrow_type' => ['required', Rule::in(['lesson', 'daily'])],

            'borrow_date' => ['required', 'date'],
            'borrow_time' => ['nullable', 'date_format:H:i'],

            // Untuk DAILY: butuh tanggal kembali (input date, nanti disimpan ke return_due datetime)
            'return_due_date' => ['nullable', 'date'],

            // Untuk LESSON: butuh jam ke-, mapel, guru
            'lesson_hour' => ['nullable', 'integer', 'min:1', 'max:12'],
            'subject' => ['nullable', 'string', 'max:100'],
            'teacher' => ['nullable', 'string', 'max:100'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $type = $this->input('borrow_type');

            if ($type === 'daily') {
                if (!$this->input('return_due_date')) {
                    $v->errors()->add('return_due_date', 'Tanggal kembali wajib untuk jenis Harian.');
                }
            }

            if ($type === 'lesson') {
                if (!$this->input('lesson_hour')) $v->errors()->add('lesson_hour', 'Jam ke- wajib untuk jenis Jam Pelajaran.');
                if (!$this->input('subject')) $v->errors()->add('subject', 'Mata pelajaran wajib untuk jenis Jam Pelajaran.');
                if (!$this->input('teacher')) $v->errors()->add('teacher', 'Nama guru wajib untuk jenis Jam Pelajaran.');
            }
        });
    }
}
