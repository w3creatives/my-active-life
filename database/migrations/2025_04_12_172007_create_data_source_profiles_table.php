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
        Schema::create('data_source_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('data_source_id')->nullable()->index('index_data_source_profiles_on_data_source_id');
            $table->bigInteger('user_id')->nullable()->index('index_data_source_profiles_on_user_id');
            $table->string('access_token')->nullable()->index('index_data_source_profiles_on_access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('access_token_secret')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->date('sync_start_date')->nullable();
            $table->timestamp('last_run_at')->nullable();

            $table->unique(['data_source_id', 'user_id'], 'idx_data_source_profiles_d_u');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_source_profiles');
    }
};
