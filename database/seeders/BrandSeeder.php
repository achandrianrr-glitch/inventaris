<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'SAMSUNG', 'status' => 'active'],
            ['name' => 'LENOVO', 'status' => 'active'],
            ['name' => 'ASUS', 'status' => 'active'],
            ['name' => 'ACER', 'status' => 'active'],
            ['name' => 'HP', 'status' => 'active'],
            ['name' => 'Thinkpad', 'status' => 'active'],
            ['name' => 'ACER', 'status' => 'active'],
        ];

        foreach ($data as $row) {
            Brand::query()->firstOrCreate(['name' => $row['name']], $row);
        }
    }
}
