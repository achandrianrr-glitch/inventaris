<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GmailOnly implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = is_string($value) ? trim($value) : '';

        // Wajib format user@gmail.com (case-insensitive)
        if (!preg_match('/^[^@\s]+@gmail\.com$/i', $email)) {
            $fail('Email wajib menggunakan domain @gmail.com.');
        }
    }
}
