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
        Schema::create('user_follows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('follower_id')->index('index_user_follows_on_follower_id');
            $table->bigInteger('followed_id')->index('index_user_follows_on_followed_id');
            $table->bigInteger('event_id')->nullable()->index('index_user_follows_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['follower_id', 'followed_id'], 'index_user_follows_on_follower_id_and_followed_id');
            $table->unique(['follower_id', 'followed_id', 'event_id'], 'index_user_follows_on_follower_id_and_followed_id_and_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_follows');
    }
};
