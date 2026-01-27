<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // bikin beberapa item pakai factory (lebih cepat + unik)
        Item::factory()->count(12)->create();
    }
}
