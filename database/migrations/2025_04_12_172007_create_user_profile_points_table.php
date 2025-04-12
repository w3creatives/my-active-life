<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_profile_points', function (Blueprint $table) {
            $table->bigInteger('data_source_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('webhook_distance_km')->nullable();
            $table->string('webhook_distance_mile')->nullable();
            $table->string('cron_distance_km')->nullable();
            $table->string('cron_distance_mile')->nullable();
            $table->date('date')->nullable();
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
            $table->timestamp('updated_at')->nullable()->default(DB::raw("now()"));
            $table->integer('id')->primary();
            $table->string('action_type', 20)->nullable()->default('auto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profile_points');
    }
};
