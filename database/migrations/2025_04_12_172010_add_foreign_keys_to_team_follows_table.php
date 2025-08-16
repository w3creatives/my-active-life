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
        Schema::table('team_follows', function (Blueprint $table) {
            $table->foreign(['follower_id'], 'fk_rails_6298099985')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['event_id'], 'fk_rails_6bbd5faf2b')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['team_id'], 'fk_rails_82af3a8a45')->references(['id'])->on('teams')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_follows', function (Blueprint $table) {
            $table->dropForeign('fk_rails_6298099985');
            $table->dropForeign('fk_rails_6bbd5faf2b');
            $table->dropForeign('fk_rails_82af3a8a45');
        });
    }
};
