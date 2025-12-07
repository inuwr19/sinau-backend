<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $order = Order::create([
            'order_number' => 'CS-20250101-PST-001',
            'branch_id' => 1,
            'user_id' => 1,
            'member_id' => 1,
            'subtotal' => 60000,
            'discount' => 6000,
            'tax' => 0,
            'total' => 54000,
            'status' => 'paid',
            'payment_method' => 'cash',
            'cash_received' => 60000,
            'change' => 6000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => 1,
            'qty' => 2,
            'unit_price' => 30000,
            'total_price' => 60000,
            'notes' => 'less sugar'
        ]);
    }
}
