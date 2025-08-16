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
        Schema::table('team_points', function (Blueprint $table) {
            $table->foreign(['event_id'], 'fk_rails_4587a2c876')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['team_id'], 'fk_rails_a6d9eac67b')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_points', function (Blueprint $table) {
            $table->dropForeign('fk_rails_4587a2c876');
            $table->dropForeign('fk_rails_a6d9eac67b');
        });
    }
};
