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
        Schema::table('displayed_user_race_milestones', function (Blueprint $table) {
            $table->foreign(['race_milestone_id'], 'fk_rails_7e4c19980c')->references(['id'])->on('race_milestones')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_rails_f276c45015')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('displayed_user_race_milestones', function (Blueprint $table) {
            $table->dropForeign('fk_rails_7e4c19980c');
            $table->dropForeign('fk_rails_f276c45015');
        });
    }
};
