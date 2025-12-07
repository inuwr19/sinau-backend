<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create([
            'name' => 'Pusat',
            'code' => 'PST',
            'address' => 'Jl. Merdeka No. 1, Kota Example'
        ]);

        Branch::create([
            'name' => 'Selatan',
            'code' => 'SLT',
            'address' => 'Jl. Kenangan No. 21, Kota Example'
        ]);
    }
}
