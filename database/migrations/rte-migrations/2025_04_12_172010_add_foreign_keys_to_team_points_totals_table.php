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
        Schema::table('team_points_totals', function (Blueprint $table) {
            $table->foreign(['event_id'], 'fk_rails_5b3788c21c')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['team_id'], 'fk_rails_a07ebf5499')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_points_totals', function (Blueprint $table) {
            $table->dropForeign('fk_rails_5b3788c21c');
            $table->dropForeign('fk_rails_a07ebf5499');
        });
    }
};
