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
        Schema::table('fits', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_rails_0a9e594269')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_id'], 'fk_rails_851a6c58a0')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fits', function (Blueprint $table) {
            $table->dropForeign('fk_rails_0a9e594269');
            $table->dropForeign('fk_rails_851a6c58a0');
        });
    }
};
