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
        Schema::create('user_follow_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('prospective_follower_id')->index('index_user_follow_requests_on_prospective_follower_id');
            $table->bigInteger('followed_id')->index('index_user_follow_requests_on_followed_id');
            $table->bigInteger('event_id')->nullable()->index('index_user_follow_requests_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->unique(['prospective_follower_id', 'followed_id', 'event_id'], 'idx_pf_u_e_rrequests');
            $table->index(['prospective_follower_id', 'followed_id'], 'idx_pm_u_rrequests');
        });
        DB::statement("alter table \"user_follow_requests\" add column \"status\" t_user_request null default 'request_to_follow_issued'");
        DB::statement("create index \"index_user_follow_requests_on_status\" on \"user_follow_requests\" (\"status\")");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_follow_requests');
    }
};
