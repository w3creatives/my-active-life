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
        Schema::table('user_follow_requests', function (Blueprint $table) {
            $table->foreign(['prospective_follower_id'], 'fk_rails_198c80e522')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['followed_id'], 'fk_rails_d9b4802171')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['event_id'], 'fk_rails_de512ded68')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_follow_requests', function (Blueprint $table) {
            $table->dropForeign('fk_rails_198c80e522');
            $table->dropForeign('fk_rails_d9b4802171');
            $table->dropForeign('fk_rails_de512ded68');
        });
    }
};
