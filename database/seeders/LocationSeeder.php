<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Lab RPL 1', 'description' => 'Ruang lab komputer Depan', 'status' => 'active'],
            ['name' => 'Lab RPL 2', 'description' => 'Ruang Lab Komputer 2', 'status' => 'active'],
            ['name' => 'lab GameLan', 'description' => 'Ruangan Game Lab', 'status' => 'active'],
            ['name' => 'lab Kantor RPL', 'description' => 'Ruangan Kantor Lab Rpl', 'status' => 'active'],
            ['name' => 'lab Depan RPL', 'description' => 'Ruangan Lab Rpl depan', 'status' => 'active'],
            ['name' => 'Gudang', 'description' => 'Ruangan Gudang Penyimpanan', 'status' => 'active'],
        ];

        foreach ($data as $row) {
            Location::query()->firstOrCreate(['name' => $row['name']], $row);
        }
    }
}
