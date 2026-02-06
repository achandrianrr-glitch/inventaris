<?php

namespace App\Support;

class ItemCode
{
    /**
     * Normalize input seperti:
     * - "lab1" / "LAB-1" / "LAB0001" / "lab 0001" -> "LAB-0001"
     * Return null jika tidak match pola 3 huruf + 1-4 digit.
     */
    public static function normalize3DigitDash4(string $input): ?string
    {
        $s = strtoupper(trim($input));
        $s = preg_replace('/\s+/', '', $s);
        $s = str_replace('_', '-', $s);

        if (preg_match('/^([A-Z]{3})-?(\d{1,4})$/', $s, $m)) {
            return $m[1] . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
        }

        return null;
    }
}
