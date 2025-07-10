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
        Schema::table('fit_life_activity_milestones', function (Blueprint $table) {
            $table->string('logo')->nullable();
            $table->string('team_logo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fit_life_activity_milestones', function (Blueprint $table) {
            //
        });
    }
};
