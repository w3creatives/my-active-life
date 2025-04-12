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
        Schema::create('shopify_webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->bigInteger('customer_id')->nullable();
            $table->boolean('webhook_status')->default(false);
            $table->boolean('tracker_status')->default(false);
            $table->boolean('hubspot_status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_webhook_events');
    }
};
