<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCountersTable extends Migration
{
    public function up()
    {
        Schema::create('order_counters', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('counter')->default(0);
            $table->timestamps();

            $table->unique(['date', 'branch_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_counters');
    }
}
