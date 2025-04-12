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
        Schema::create('fit_life_activity_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('activity_id')->index('index_fit_life_activity_milestones_on_activity_id');
            $table->string('name')->index('index_fit_life_activity_milestones_on_name');
            $table->float('total_points');
            $table->json('data')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_life_activity_milestones');
    }
};
