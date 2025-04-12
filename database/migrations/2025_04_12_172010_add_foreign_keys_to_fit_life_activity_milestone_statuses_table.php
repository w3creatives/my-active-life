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
        Schema::table('fit_life_activity_milestone_statuses', function (Blueprint $table) {
            $table->foreign(['registration_id'], 'fk_rails_0e364f53af')->references(['id'])->on('fit_life_activity_registrations')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['milestone_id'], 'fk_rails_46c7ad3bc2')->references(['id'])->on('fit_life_activity_milestones')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_rails_5091ac86d3')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fit_life_activity_milestone_statuses', function (Blueprint $table) {
            $table->dropForeign('fk_rails_0e364f53af');
            $table->dropForeign('fk_rails_46c7ad3bc2');
            $table->dropForeign('fk_rails_5091ac86d3');
        });
    }
};
