<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('points_history', function (Blueprint $table) {
            // Tambah hanya jika belum ada, supaya aman kalau migration pernah dijalankan
            if (!Schema::hasColumn('points_history', 'member_id')) {
                $table->foreignId('member_id')
                    ->after('id')
                    ->constrained('members')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('points_history', 'order_id')) {
                $table->foreignId('order_id')
                    ->after('member_id')
                    ->constrained('orders')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('points_history', 'points_change')) {
                $table->integer('points_change')->after('order_id');
            }

            if (!Schema::hasColumn('points_history', 'reason')) {
                $table->string('reason')->nullable()->after('points_change');
            }
        });
    }

    public function down(): void
    {
        Schema::table('points_history', function (Blueprint $table) {
            if (Schema::hasColumn('points_history', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }
            if (Schema::hasColumn('points_history', 'member_id')) {
                $table->dropConstrainedForeignId('member_id');
            }
            if (Schema::hasColumn('points_history', 'points_change')) {
                $table->dropColumn('points_change');
            }
            if (Schema::hasColumn('points_history', 'reason')) {
                $table->dropColumn('reason');
            }
        });
    }
};
