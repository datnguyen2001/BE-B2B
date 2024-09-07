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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->integer('shop_id');
            $table->integer('user_id');
            $table->string('name');
            $table->string('phone');
            $table->string('province_id');
            $table->string('district_id');
            $table->string('ward_id');
            $table->string('address_detail');
            $table->longText('note')->nullable();
            $table->string('shipping_unit')->nullable();
            $table->string('order_code_transport')->nullable();
            $table->integer('commodity_money')->default(0);
            $table->integer('shipping_fee')->default(0);
            $table->integer('total_payment')->default(0);
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
