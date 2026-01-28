<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Set kredensial admin utama di sini
        $name = 'Admin Inventary';
        $email = 'inventaryy@gmail.com';
        $password = 'adminsmk9';

        // Sistem admin-only: ambil user pertama kalau sudah ada
        $user = User::query()->orderBy('id')->first();

        if ($user) {
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => $password,      // auto-hash via cast 'hashed' di Model
                'status' => 'active',
                'email_verified_at' => now(), // biar langsung bisa akses /admin
                'last_login' => null,
            ])->save();

            return;
        }

        // Kalau tabel users kosong, buat admin baru
        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'status' => 'active',
            'email_verified_at' => now(),
            'last_login' => null,
        ]);
    }
}
