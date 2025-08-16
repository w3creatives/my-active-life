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
        Schema::create('team_follows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('follower_id')->index('index_team_follows_on_follower_id');
            $table->bigInteger('team_id')->index('index_team_follows_on_team_id');
            $table->bigInteger('event_id')->nullable()->index('index_team_follows_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->unique(['follower_id', 'team_id', 'event_id'], 'idx_t_r_follower_team_event');
            $table->index(['follower_id', 'team_id'], 'index_team_follows_on_follower_id_and_team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_follows');
    }
};
