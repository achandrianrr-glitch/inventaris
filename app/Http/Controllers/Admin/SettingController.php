<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private function getSingleton(): Setting
    {
        return Setting::query()->firstOrCreate(
            ['id' => 1],
            [
                'school_name' => 'SMKN 9 MALANG',
                'city' => 'Malang',
                'code_format' => 'INV-{YYYY}-{SEQ4}',
                'notification_email' => null,
                'notification_wa' => null,
            ]
        );
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'setting' => $this->getSingleton(),
            'codeHelp' => [
                'placeholders' => [
                    '{YYYY}' => 'Tahun 4 digit (2026)',
                    '{YY}' => 'Tahun 2 digit (26)',
                    '{MM}' => 'Bulan 2 digit (01-12)',
                    '{SEQ4}' => 'Nomor urut 4 digit (0001)',
                    '{SEQ3}' => 'Nomor urut 3 digit (001)',
                ],
                'examples' => [
                    'INV-{YYYY}-{SEQ4}' => 'INV-2026-0001',
                    'LAB-{YY}{MM}-{SEQ3}' => 'LAB-2602-001',
                ],
            ],
        ]);
    }

    public function update(SettingUpdateRequest $request)
    {
        $setting = $this->getSingleton();

        $before = [
            'school_name' => $setting->school_name,
            'city' => $setting->city,
            'code_format' => $setting->code_format,
            'notification_email' => $setting->notification_email,
            'notification_wa' => $setting->notification_wa,
        ];

        $data = $request->validated();

        $setting->update($data);

        $after = [
            'school_name' => $setting->school_name,
            'city' => $setting->city,
            'code_format' => $setting->code_format,
            'notification_email' => $setting->notification_email,
            'notification_wa' => $setting->notification_wa,
        ];

        $changes = [];
        foreach ($before as $k => $v) {
            $av = $after[$k] ?? null;
            if ($av !== $v) {
                $changes[] = "{$k}: " . ($v === null || $v === '' ? '-' : $v) . " → " . ($av === null || $av === '' ? '-' : $av);
            }
        }

        $desc = "Update pengaturan sistem (setting_id={$setting->id})";
        $desc .= !empty($changes) ? " | " . implode(' | ', $changes) : " | tidak ada perubahan";

        // LOG AKTIVITAS
        activity_log('settings', 'update', $desc);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
