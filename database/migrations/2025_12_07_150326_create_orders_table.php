<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled', 'delivered'])->default('pending');
            $table->enum('payment_method', ['cash', 'card', 'qris'])->nullable();
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change', 12, 2)->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('orders');
    }

};
