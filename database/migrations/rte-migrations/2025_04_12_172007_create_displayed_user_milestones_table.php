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
        Schema::create('displayed_user_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable()->index('index_displayed_user_milestones_on_user_id');
            $table->bigInteger('event_milestone_id')->nullable()->index('index_displayed_user_milestones_on_event_milestone_id');
            $table->boolean('displayed')->nullable()->default(false);
            $table->boolean('emailed')->nullable()->default(false);
            $table->boolean('individual')->nullable()->default(true)->index('idx_u_d_milestones_i');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['user_id', 'event_milestone_id'], 'idx_u_d_milestones_u_e');
            $table->index(['user_id', 'event_milestone_id', 'displayed'], 'idx_u_d_milestones_u_e_d');
            $table->index(['user_id', 'event_milestone_id', 'emailed'], 'idx_u_d_milestones_u_e_e');
            $table->index(['user_id', 'event_milestone_id', 'individual'], 'idx_u_d_milestones_u_e_i');
            $table->index(['user_id', 'event_milestone_id', 'individual', 'displayed'], 'idx_u_d_milestones_u_e_i_d');
            $table->index(['user_id', 'event_milestone_id', 'individual', 'emailed'], 'idx_u_d_milestones_u_e_i_e');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('displayed_user_milestones');
    }
};
