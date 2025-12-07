<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['cash', 'card', 'qris']);
            $table->decimal('amount', 12, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('payments');
    }

};
