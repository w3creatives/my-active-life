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
        Schema::create('race_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('status')->nullable();
            $table->string('name')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->bigInteger('race_id')->nullable()->index('index_race_milestones_on_race_id');
            $table->float('distance')->nullable();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_milestones');
    }
};
