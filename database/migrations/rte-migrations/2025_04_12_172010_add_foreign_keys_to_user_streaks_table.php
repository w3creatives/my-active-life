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
        Schema::table('user_streaks', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_rails_4b8f846796')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_id'], 'fk_rails_7884131613')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_streak_id'], 'fk_rails_7a7173a653')->references(['id'])->on('event_streaks')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_streaks', function (Blueprint $table) {
            $table->dropForeign('fk_rails_4b8f846796');
            $table->dropForeign('fk_rails_7884131613');
            $table->dropForeign('fk_rails_7a7173a653');
        });
    }
};
