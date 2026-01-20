<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //$products = Product::factory(50)->create();
        User::factory()->create();

//        $categories = Category::pluck('id');
//
//        foreach ($products as $product) {
//            $randomCategories = $categories
//                ->random(rand(1, min($categories->count(),3)))
//                ->toArray();
//
//            $product->categories()->sync($randomCategories);
//        }
    }
}
