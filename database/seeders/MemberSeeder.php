<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'Andi Putra', 'phone' => '0811000001', 'points' => 20],
            ['name' => 'Budi Santoso', 'phone' => '0811000002', 'points' => 0],
            ['name' => 'Citra Dewi', 'phone' => '0811000003', 'points' => 50],
        ];

        foreach ($members as $m) {
            Member::create($m);
        }
    }
}
