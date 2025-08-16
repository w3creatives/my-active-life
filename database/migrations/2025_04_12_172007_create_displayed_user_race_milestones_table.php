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
        Schema::create('displayed_user_race_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable()->index('index_displayed_user_race_milestones_on_user_id');
            $table->bigInteger('race_milestone_id')->nullable()->index('index_displayed_user_race_milestones_on_race_milestone_id');
            $table->boolean('displayed')->nullable()->default(false);
            $table->boolean('emailed')->nullable()->default(false);
            $table->boolean('individual')->nullable()->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['user_id', 'race_milestone_id'], 'idx_u_d_milestones_u_r');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('displayed_user_race_milestones');
    }
};
