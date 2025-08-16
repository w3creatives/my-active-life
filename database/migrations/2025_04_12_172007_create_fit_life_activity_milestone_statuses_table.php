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
        Schema::create('fit_life_activity_milestone_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('milestone_id')->index('index_fit_life_activity_milestone_statuses_on_milestone_id');
            $table->bigInteger('user_id')->index('index_fit_life_activity_milestone_statuses_on_user_id');
            $table->boolean('displayed')->default(false);
            $table->boolean('emailed')->default(false);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->bigInteger('registration_id')->index('index_fit_life_activity_milestone_statuses_on_registration_id');

            $table->index(['milestone_id', 'user_id'], 'milestone_id_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_life_activity_milestone_statuses');
    }
};
