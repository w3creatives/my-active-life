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
        Schema::table('team_achievements', function (Blueprint $table) {
            $table->foreign(['team_id'], 'fk_rails_8b3e3a40a9')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_id'], 'fk_rails_eb68b9ed6b')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_achievements', function (Blueprint $table) {
            $table->dropForeign('fk_rails_8b3e3a40a9');
            $table->dropForeign('fk_rails_eb68b9ed6b');
        });
    }
};
