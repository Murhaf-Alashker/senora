<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sizes = [32,34,36,38,40,42];
        $i = rand(0, count($sizes) - 1);
        $element = [];
        $all_color = [];
        for($x = 0; $x <= $i; $x++) {
            $element[] = $this->faker->randomElement($sizes);
            $sizes = array_filter($sizes, fn($v) => $v != $element);
        }
        $colors = ['احمر','اخضر','اصفر','زهري','اسود'];
        $i = rand(0, count($colors) - 1);
        for($x = 0; $x <= $i; $x++) {
            $all_color[] = $this->faker->randomElement($colors);
            $colors = array_filter($colors, fn($v) => $v != $all_color);
        }
        $categories = Category::pluck('id')->toArray();
        return [
            'name' => fake()->name(),
            'price' => rand(1, 100),
            'custom_tailoring' => fake()->boolean(),
            'visitor' => rand(1, 100),
            'orders' => rand(1, 100),
            'category_id' => $this->faker->randomElement($categories),
            'sizes' => $element,
            'colors' => $all_color,
        ];
    }
}
