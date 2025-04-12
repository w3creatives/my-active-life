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
        Schema::create('event_participations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('event_id')->nullable()->index('index_event_participations_on_event_id');
            $table->bigInteger('user_id')->nullable()->index('index_event_participations_on_user_id');
            $table->boolean('public_profile')->nullable()->default(false);
            $table->boolean('include_daily_steps')->nullable()->default(false);
            $table->boolean('subscribed')->nullable();
            $table->date('subscription_end_date')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->string('note')->nullable();
            $table->date('subscription_start_date')->nullable();

            $table->unique(['event_id', 'user_id'], 'index_event_participations_on_event_id_and_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_participations');
    }
};
