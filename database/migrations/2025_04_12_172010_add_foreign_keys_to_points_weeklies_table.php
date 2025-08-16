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
        Schema::table('points_weeklies', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_rails_71aa03b62a')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_id'], 'fk_rails_a2fe56e23d')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('points_weeklies', function (Blueprint $table) {
            $table->dropForeign('fk_rails_71aa03b62a');
            $table->dropForeign('fk_rails_a2fe56e23d');
        });
    }
};
