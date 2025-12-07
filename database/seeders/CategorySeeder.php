<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Coffee', 'slug' => 'coffee'],
            ['name' => 'Non-Coffee', 'slug' => 'non-coffee'],
            ['name' => 'Food', 'slug' => 'food'],
            ['name' => 'Snack', 'slug' => 'snack'],
        ];

        foreach ($categories as $c) {
            Category::create($c);
        }
    }
}
