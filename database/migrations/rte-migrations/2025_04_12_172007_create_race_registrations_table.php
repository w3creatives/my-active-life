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
        Schema::create('race_registrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('race_id')->nullable()->index('index_race_registrations_on_race_id');
            $table->bigInteger('user_id')->nullable()->index('index_race_registrations_on_user_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->float('distance')->nullable();
            $table->date('date')->nullable();

            $table->index(['race_id', 'user_id'], 'index_race_registrations_on_race_id_and_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_registrations');
    }
};
