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
        Schema::table('team_follow_requests', function (Blueprint $table) {
            $table->foreign(['prospective_follower_id'], 'fk_rails_071bb88530')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['team_id'], 'fk_rails_4725b2bc27')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['event_id'], 'fk_rails_a816447df8')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_follow_requests', function (Blueprint $table) {
            $table->dropForeign('fk_rails_071bb88530');
            $table->dropForeign('fk_rails_4725b2bc27');
            $table->dropForeign('fk_rails_a816447df8');
        });
    }
};
