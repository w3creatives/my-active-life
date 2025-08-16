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
        Schema::table('team_points_monthlies', function (Blueprint $table) {
            $table->foreign(['team_id'], 'fk_rails_7445dbce79')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_id'], 'fk_rails_933af6f41b')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_points_monthlies', function (Blueprint $table) {
            $table->dropForeign('fk_rails_7445dbce79');
            $table->dropForeign('fk_rails_933af6f41b');
        });
    }
};
