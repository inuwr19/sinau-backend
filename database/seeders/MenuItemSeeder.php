<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Coffee
            [
                'name' => 'Cappuccino',
                'description' => 'Espresso dengan steamed milk dan foam.',
                'price' => 30000,
                'category_id' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],
            [
                'name' => 'Latte',
                'description' => 'Kopi susu lembut dengan microfoam.',
                'price' => 28000,
                'category_id' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],

            // Non-coffee
            [
                'name' => 'Matcha Latte',
                'description' => 'Matcha premium dengan susu.',
                'price' => 32000,
                'category_id' => 2,
                'image_url' => 'https://images.unsplash.com/photo-1542144612-1b7f0f7a6f2b?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],
            [
                'name' => 'Chocolate Ice',
                'description' => 'Minuman coklat dingin manis.',
                'price' => 25000,
                'category_id' => 2,
                'image_url' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],

            // Food
            [
                'name' => 'Chicken Rice Bowl',
                'description' => 'Nasi dengan chicken karaage & saus spesial.',
                'price' => 35000,
                'category_id' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1604908177522-6f3a3f298f5e?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],
            [
                'name' => 'Beef Donburi',
                'description' => 'Nasi dengan beef slice premium.',
                'price' => 42000,
                'category_id' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1562967914-6088e2f3a0a3?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],

            // Snack
            [
                'name' => 'French Fries',
                'description' => 'Kentang goreng renyah.',
                'price' => 20000,
                'category_id' => 4,
                'image_url' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],
            [
                'name' => 'Chicken Wings',
                'description' => 'Sayap ayam pedas gurih.',
                'price' => 30000,
                'category_id' => 4,
                'image_url' => 'https://images.unsplash.com/photo-1604908554028-6d5b1d3a3a2e?auto=format&fit=crop&w=800&q=60',
                'is_available' => true,
            ],
        ];

        foreach ($items as $item) {
            MenuItem::create($item);
        }
    }
}
