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
        Schema::create('team_points_monthlies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('amount')->nullable()->default(0);
            $table->date('date')->nullable()->index('index_team_points_monthlies_on_date');
            $table->bigInteger('team_id')->nullable()->index('index_team_points_monthlies_on_team_id');
            $table->bigInteger('event_id')->nullable()->index('index_team_points_monthlies_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['event_id', 'team_id'], 'index_team_points_monthlies_on_event_id_and_team_id');
            $table->index(['event_id', 'team_id', 'date'], 'index_team_points_monthlies_on_event_id_and_team_id_and_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_points_monthlies');
    }
};
