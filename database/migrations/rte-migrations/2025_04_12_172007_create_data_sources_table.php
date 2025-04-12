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
        Schema::create('data_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique('index_data_sources_on_name');
            $table->string('short_name');
            $table->text('description')->nullable();
            $table->boolean('resynchronizable')->nullable()->default(false);
            $table->json('profile')->nullable()->default('{}');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
