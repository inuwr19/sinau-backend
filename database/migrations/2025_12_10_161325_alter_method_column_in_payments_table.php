<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kalau sebelumnya kolom `method` bertipe ENUM atau VARCHAR pendek,
        // kita ubah jadi VARCHAR(50) supaya bisa simpan:
        // 'cash', 'va', 'qris', 'bca_va', 'gopay', 'shopeepay', dll.
        DB::statement('ALTER TABLE payments MODIFY `method` VARCHAR(50) NOT NULL');
    }

    public function down(): void
    {
        // Kalau mau, bisa kembalikan ke bentuk awal (misalnya ENUM).
        // Sesuaikan dengan kondisi awal Anda.
        // Contoh kalau awalnya ENUM:
        // DB::statement("ALTER TABLE payments MODIFY `method` ENUM('cash','card','qris') NOT NULL");
    }
};
