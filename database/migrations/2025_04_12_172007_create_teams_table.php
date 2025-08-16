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
        Schema::create('teams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->fulltext('idx_teams_name_gin');
            $table->bigInteger('event_id')->nullable()->index('index_teams_on_event_id');
            $table->bigInteger('owner_id')->nullable();
            $table->boolean('public_profile')->nullable()->default(false);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->jsonb('settings')->default('{}');

            $table->index(['name'], 'index_teams_on_name');
            $table->unique(['name', 'event_id'], 'index_teams_on_name_and_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
