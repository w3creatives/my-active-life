<?php

declare(strict_types=1);

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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('data_source_id')->nullable()->index('index_webhook_logs_on_data_source_id');
            $table->bigInteger('user_id')->nullable()->index('index_webhook_logs_on_user_id');
            $table->string('access_token')->nullable()->index('index_webhook_logs_on_access_token');
            $table->string('file_name')->nullable();
            $table->string('callback_url')->nullable();
            $table->json('callback_data')->nullable();
            $table->json('parsed_data')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
