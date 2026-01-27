<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Monitor', 'description' => 'Layar Komputer', 'status' => 'active'],
            ['name' => 'Pc', 'description' => 'Alat Komputer', 'status' => 'active'],
            ['name' => 'Komputer', 'description' => 'Perangkat komputer & jaringan', 'status' => 'active'],
            ['name' => 'Laptop', 'description' => 'Alat Belajar', 'status' => 'active'],
            ['name' => 'Mouse', 'description' => 'Alat Komputer', 'status' => 'active'],
            ['name' => 'MousePad', 'description' => 'Alat Komputer', 'status' => 'active'],
            ['name' => 'Keyboard', 'description' => 'Alat Komputer', 'status' => 'active'],
            ['name' => 'Kursi', 'description' => 'Tempat Duduk Lab', 'status' => 'active'],
            ['name' => 'Meja', 'description' => 'Tempat Belajar', 'status' => 'active'],
            ['name' => 'Layar Tv', 'description' => 'Alat Lab RPL', 'status' => 'active'],
            ['name' => 'Charging Laptop', 'description' => 'Alat Laptop', 'status' => 'active'],
        ];

        foreach ($data as $row) {
            Category::query()->firstOrCreate(['name' => $row['name']], $row);
        }
    }
}
