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
        Schema::create('fit_life_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sponsor');
            $table->bigInteger('event_id')->index('index_fit_life_activities_on_event_id');
            $table->string('category');
            $table->string('group');
            $table->string('name')->index('index_fit_life_activities_on_name');
            $table->text('description');
            $table->string('tags')->nullable();
            $table->float('total_points')->default(0);
            $table->string('social_hashtags')->nullable();
            $table->string('sports')->default('{RUNNING}');
            $table->date('available_from')->index('index_fit_life_activities_on_available_from');
            $table->date('available_until')->index('index_fit_life_activities_on_available_until');
            $table->json('data')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['available_from', 'available_until'], 'index_fit_life_activities_on_available_from_and_available_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_life_activities');
    }
};
