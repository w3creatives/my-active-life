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
        Schema::create('user_data_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('data_source_id')->nullable()->index('index_user_data_sources_on_data_source_id');
            $table->bigInteger('user_id')->nullable()->index('index_user_data_sources_on_user_id');
            $table->bigInteger('event_id')->nullable()->index('index_user_data_sources_on_event_id');

            $table->unique(['data_source_id', 'user_id', 'event_id'], 'idx_user_data_sources_d_u_e');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_data_sources');
    }
};
