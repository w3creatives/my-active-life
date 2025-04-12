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
        Schema::create('shopify_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id');
            $table->string('order_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('customer_id')->nullable();
            $table->decimal('total_price', 10)->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_type')->nullable();
            $table->string('product_tags')->nullable();
            $table->text('meta_fields')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('product_sku')->nullable();
            $table->string('variant_id')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->boolean('tracker_status')->nullable()->default(false);
            $table->boolean('hubspot_status')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_orders');
    }
};
