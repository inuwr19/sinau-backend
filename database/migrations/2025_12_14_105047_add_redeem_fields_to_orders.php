<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'redeemed_points')) {
                $table->integer('redeemed_points')->default(0)->after('discount');
            }
            if (!Schema::hasColumn('orders', 'redeem_discount')) {
                $table->decimal('redeem_discount', 10, 2)->default(0)->after('redeemed_points');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'redeem_discount')) {
                $table->dropColumn('redeem_discount');
            }
            if (Schema::hasColumn('orders', 'redeemed_points')) {
                $table->dropColumn('redeemed_points');
            }
        });
    }
};
