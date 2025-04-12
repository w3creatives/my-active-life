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
        Schema::table('user_points', function (Blueprint $table) {
            $table->foreign(['event_id'], 'fk_rails_09b3bb3549')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['data_source_id'], 'fk_rails_658d0c2153')->references(['id'])->on('data_sources')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_rails_b9622a6aeb')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_points', function (Blueprint $table) {
            $table->dropForeign('fk_rails_09b3bb3549');
            $table->dropForeign('fk_rails_658d0c2153');
            $table->dropForeign('fk_rails_b9622a6aeb');
        });
    }
};
