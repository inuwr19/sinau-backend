<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin global (melihat semua cabang)
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@cafesinau.test',
            'password' => bcrypt('password'),
            'branch_id' => 1,
            'role' => 'admin',
        ]);

        // Cashier cabang Pusat
        User::create([
            'name' => 'Cashier Pusat',
            'email' => 'cashier.pusat@test.com',
            'password' => bcrypt('password'),
            'branch_id' => 1,
            'role' => 'cashier',
        ]);

        // Cashier cabang Selatan
        User::create([
            'name' => 'Cashier Selatan',
            'email' => 'cashier.selatan@test.com',
            'password' => bcrypt('password'),
            'branch_id' => 2,
            'role' => 'cashier',
        ]);
    }
}
