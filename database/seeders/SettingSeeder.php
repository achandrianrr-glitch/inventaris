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
                'school_name' => 'SMK Contoh Negeri 1',
                'city' => 'Jakarta',
                'code_format' => 'INV-{YEAR}-{SEQ}',
                'notification_email' => 'admin@gmail.com',
                'notification_wa' => null,
            ]
        );
    }
}
