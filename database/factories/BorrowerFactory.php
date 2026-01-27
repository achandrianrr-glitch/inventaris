<?php

namespace Database\Factories;

use App\Models\Borrower;
use Illuminate\Database\Eloquent\Factories\Factory;

class BorrowerFactory extends Factory
{
    protected $model = Borrower::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['student', 'teacher']);

        return [
            'name' => fake()->name(),
            'type' => $type,
            'class' => $type === 'student' ? fake()->randomElement(['X', 'XI', 'XII']) . ' ' . fake()->randomElement(['A', 'B', 'C']) : null,
            'major' => $type === 'student' ? fake()->randomElement(['RPL', 'TKJ', 'MM', 'OTKP']) : null,
            'id_number' => fake()->unique()->numerify('##########'),
            'contact' => fake()->numerify('08##########'),
            'status' => 'active',
        ];
    }
}
