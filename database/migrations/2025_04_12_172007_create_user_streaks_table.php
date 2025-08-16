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
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('event_streak_id')->nullable()->index('index_user_streaks_on_event_streak_id');
            $table->date('date')->nullable();
            $table->bigInteger('user_id')->nullable()->index('index_user_streaks_on_user_id');
            $table->bigInteger('event_id')->nullable()->index('index_user_streaks_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
    }
};
