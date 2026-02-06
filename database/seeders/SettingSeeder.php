<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'SMKN 9 MALANG',
                'city' => 'Malang',
                'code_format' => 'INV-{YEAR}-{SEQ}',
                'notification_email' => 'inventaryy@gmail.com',
                'notification_wa' => null,
            ]
        );
    }
}
