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
        Schema::table('team_membership_requests', function (Blueprint $table) {
            $table->foreign(['prospective_member_id'], 'fk_rails_21ba35cd54')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['event_id'], 'fk_rails_2a66d09818')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['team_id'], 'fk_rails_9f9c160e9d')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_membership_requests', function (Blueprint $table) {
            $table->dropForeign('fk_rails_21ba35cd54');
            $table->dropForeign('fk_rails_2a66d09818');
            $table->dropForeign('fk_rails_9f9c160e9d');
        });
    }
};
