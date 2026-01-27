<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Lab',
                'password' => 'password123', // otomatis ke-hash karena casts password => hashed
                'status' => 'active',
                'last_login' => null,
            ]
        );
    }
}
