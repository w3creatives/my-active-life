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
        Schema::create('fit_life_activity_registrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->index('index_fit_life_activity_registrations_on_user_id');
            $table->bigInteger('activity_id')->index('index_fit_life_activity_registrations_on_activity_id');
            $table->date('date')->index('index_fit_life_activity_registrations_on_date');
            $table->text('notes')->nullable();
            $table->json('data')->nullable();
            $table->boolean('archived')->default(false);
            $table->boolean('shared')->default(false);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->string('image')->nullable();

            $table->index(['user_id', 'date'], 'index_fit_life_activity_registrations_on_user_id_and_date');
            $table->index(['user_id', 'activity_id'], 'user_id_activitiy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_life_activity_registrations');
    }
};
