<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SettingSeeder::class,

            CategorySeeder::class,
            BrandSeeder::class,
            LocationSeeder::class,
            ItemSeeder::class,
            BorrowerSeeder::class,

            // dummy transaksi biar enak test UI nanti
            TransactionSeeder::class,
            BorrowingSeeder::class,
            DamageSeeder::class,
            StockOpnameSeeder::class,
            NotificationSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
