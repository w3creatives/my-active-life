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
        Schema::create('activity_transaction_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->nullable();
            $table->string('transaction_id')->nullable()->index('index_activity_transaction_ids_on_transaction_id');
            $table->bigInteger('user_id')->nullable()->index('index_activity_transaction_ids_on_user_id');
            $table->bigInteger('data_source_id')->nullable()->index('index_activity_transaction_ids_on_data_source_id');
            $table->bigInteger('event_id')->nullable()->index('index_activity_transaction_ids_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_transaction_ids');
    }
};
