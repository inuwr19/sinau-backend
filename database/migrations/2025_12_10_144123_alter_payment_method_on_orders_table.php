<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ubah kolom payment_method jadi VARCHAR(50) nullable
        DB::statement('ALTER TABLE orders MODIFY payment_method VARCHAR(50) NULL');
    }

    public function down(): void
    {
        // Kalau sebelumnya enum, sesuaikan dengan kondisi awal kamu
        // Contoh kalau awalnya:
        // ENUM('cash','card','qris')
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cash','card','qris') NULL");
    }
};
