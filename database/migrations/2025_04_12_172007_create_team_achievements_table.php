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
        Schema::create('team_achievements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('team_id')->nullable()->index('index_team_achievements_on_team_id');
            $table->bigInteger('event_id')->nullable()->index('index_team_achievements_on_event_id');
            $table->float('accomplishment');
            $table->date('date');
            $table->string('achievement');
            $table->boolean('notified')->nullable()->default(false);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
        //DB::statement("alter table \"team_achievements\" add column \"achievement\" t_achievement not null");
        DB::statement("create index \"index_team_achievements_on_team_id_and_achievement\" on \"team_achievements\" (\"team_id\", \"achievement\")");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_achievements');
    }
};
