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
        Schema::create('team_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('team_id')->nullable()->index('index_team_memberships_on_team_id');
            $table->bigInteger('user_id')->nullable()->index('index_team_memberships_on_user_id');
            $table->bigInteger('event_id')->nullable()->index('index_team_memberships_on_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_memberships');
    }
};
