<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(5, 30);

        return [
            'code' => 'ITM-' . fake()->unique()->numerify('####'),
            'name' => ucfirst(fake()->words(3, true)),

            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'brand_id' => Brand::query()->inRandomOrder()->value('id') ?? Brand::factory(),
            'location_id' => Location::query()->inRandomOrder()->value('id') ?? Location::factory(),

            'specification' => fake()->sentence(),
            'purchase_year' => (int) fake()->numberBetween(2018, (int) date('Y')),
            'purchase_price' => fake()->randomFloat(2, 50000, 5000000),

            'stock_total' => $total,
            'stock_available' => $total,
            'stock_borrowed' => 0,
            'stock_damaged' => 0,

            'condition' => 'good',
            'status' => 'active',
        ];
    }
}
