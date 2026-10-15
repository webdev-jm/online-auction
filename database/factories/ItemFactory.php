<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_number' => 'IT-'.fake()->unique()->numerify('#####'),
            'name' => fake()->randomElement(['Laptop', 'Monitor', 'Desktop PC', 'Tablet', 'Printer']).' '.fake()->bothify('##??'),
            'brand' => fake()->randomElement(['Lenovo', 'Dell', 'HP', 'Acer', 'Apple']),
        ];
    }
}
