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
        Schema::create('team_follow_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('prospective_follower_id')->index('index_team_follow_requests_on_prospective_follower_id');
            $table->bigInteger('team_id')->index('index_team_follow_requests_on_team_id');
            $table->bigInteger('event_id')->nullable()->index('index_team_follow_requests_on_event_id');
            $table->string('status')->nullable()->default('request_to_follow_issued');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->unique(['prospective_follower_id', 'team_id', 'event_id'], 'idx_pf_t_e_rrequests');
            $table->index(['prospective_follower_id', 'team_id'], 'idx_pm_t_rrequests');
        });
        //DB::statement("alter table \"team_follow_requests\" add column \"status\" t_team_request null default 'request_to_follow_issued'");
        DB::statement("create index \"index_team_follow_requests_on_status\" on \"team_follow_requests\" (\"status\")");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_follow_requests');
    }
};
