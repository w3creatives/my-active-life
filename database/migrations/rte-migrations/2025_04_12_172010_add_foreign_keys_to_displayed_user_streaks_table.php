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
        Schema::table('displayed_user_streaks', function (Blueprint $table) {
            $table->foreign(['event_streak_id'], 'fk_rails_9342b99208')->references(['id'])->on('event_streaks')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_rails_e9b1e6739b')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('displayed_user_streaks', function (Blueprint $table) {
            $table->dropForeign('fk_rails_9342b99208');
            $table->dropForeign('fk_rails_e9b1e6739b');
        });
    }
};
