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
        Schema::table('fit_life_activity_milestones', function (Blueprint $table) {
            $table->foreign(['activity_id'], 'fk_rails_97d1ff47e9')->references(['id'])->on('fit_life_activities')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fit_life_activity_milestones', function (Blueprint $table) {
            $table->dropForeign('fk_rails_97d1ff47e9');
        });
    }
};
