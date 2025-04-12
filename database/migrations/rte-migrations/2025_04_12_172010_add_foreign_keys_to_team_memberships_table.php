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
        Schema::table('team_memberships', function (Blueprint $table) {
            $table->foreign(['event_id'], 'fk_rails_156358855c')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_rails_5aba9331a7')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['team_id'], 'fk_rails_61c29b529e')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_memberships', function (Blueprint $table) {
            $table->dropForeign('fk_rails_156358855c');
            $table->dropForeign('fk_rails_5aba9331a7');
            $table->dropForeign('fk_rails_61c29b529e');
        });
    }
};
