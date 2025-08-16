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
        Schema::create('event_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index('index_event_milestones_on_name');
            $table->text('description')->nullable();
            $table->float('distance')->default(0);
            $table->json('data')->nullable()->default('{}');
            $table->bigInteger('event_id')->nullable()->index('index_event_milestones_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->string('logo')->nullable();
            $table->string('team_logo')->nullable();

            $table->index(['name', 'event_id'], 'index_event_milestones_on_name_and_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_milestones');
    }
};
