<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable()->index('index_user_achievements_on_user_id');
            $table->bigInteger('event_id')->nullable()->index('index_user_achievements_on_event_id');
            $table->float('accomplishment');
            $table->date('date')->index('index_user_achievements_on_date');
            $table->string('achievement');
            $table->boolean('notified')->nullable()->default(false);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['user_id', 'event_id'], 'index_user_achievements_on_user_id_and_event_id');
            $table->index(['user_id', 'event_id', 'date'], 'index_user_achievements_on_user_id_and_event_id_and_date');
        });
        // DB::statement("alter table \"user_achievements\" add column \"achievement\" t_achievement not null");
        DB::statement('create index "index_user_achievements_on_user_id_and_achievement" on "user_achievements" ("user_id", "achievement")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
