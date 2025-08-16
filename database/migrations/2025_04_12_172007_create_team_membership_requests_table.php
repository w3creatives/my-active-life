<?php

declare(strict_types=1);

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
        Schema::create('team_membership_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('prospective_member_id')->index('index_team_membership_requests_on_prospective_member_id');
            $table->bigInteger('team_id')->index('index_team_membership_requests_on_team_id');
            $table->bigInteger('event_id')->nullable()->index('index_team_membership_requests_on_event_id');
            $table->string('status')->nullable()->default('request_to_join_issued');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->unique(['prospective_member_id', 'team_id', 'event_id'], 'idx_pm_t_e_mrequests');
            $table->index(['prospective_member_id', 'team_id'], 'idx_pm_t_mrequests');
        });
        // DB::statement("alter table \"team_membership_requests\" add column \"status\" t_team_request null default 'request_to_join_issued'");
        DB::statement('create index "index_team_membership_requests_on_status" on "team_membership_requests" ("status")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_membership_requests');
    }
};
