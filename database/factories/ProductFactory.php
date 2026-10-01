<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);
        $price = fake()->randomFloat(2, 2, 20);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'category' => fake()->randomElement(['jewelry', 'eyewear', 'bags', 'hair', 'charms']),
            'price_usd' => $price,
            'price_khr' => (int) round($price * 4100),
            'stock' => 10,
            'image' => 'https://example.com/product.jpg',
            'status' => 'active',
        ];
    }
}
