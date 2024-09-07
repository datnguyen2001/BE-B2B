<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_total', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable();
            $table->integer('type_payment')->default(1);
            $table->integer('total_product')->default(0);
            $table->integer('total_shipping_fee')->default(0);
            $table->integer('exchange_points')->default(0);
            $table->integer('total_payment')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_total');
    }
};
