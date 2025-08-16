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
        Schema::table('event_streaks', function (Blueprint $table) {
            $table->foreign(['event_id'], 'fk_rails_51514ffb1d')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_streaks', function (Blueprint $table) {
            $table->dropForeign('fk_rails_51514ffb1d');
        });
    }
};
