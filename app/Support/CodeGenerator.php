<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CodeGenerator
{
    public static function make(string $prefixKey, int $seq, ?string $format = null): string
    {
        $s = Setting::query()->find(1);
        $fmt = $format ?: ($s?->code_format ?? 'INV-{YYYY}-{SEQ4}');

        $now = Carbon::now();
        $map = [
            '{YYYY}' => $now->format('Y'),
            '{YY}'   => $now->format('y'),
            '{MM}'   => $now->format('m'),
            '{SEQ4}' => str_pad((string)$seq, 4, '0', STR_PAD_LEFT),
            '{SEQ3}' => str_pad((string)$seq, 3, '0', STR_PAD_LEFT),
        ];

        return strtr($fmt, $map);
    }

    /**
     * Ambil nomor urut aman (transaksi) berbasis table_name.
     */
    public static function nextSequence(string $tableName): int
    {
        // cara simpel: ambil max id + 1 (cukup untuk sekolah; kalau mau super aman bisa pakai table sequences)
        $maxId = DB::table($tableName)->max('id') ?? 0;
        return (int)$maxId + 1;
    }
}
