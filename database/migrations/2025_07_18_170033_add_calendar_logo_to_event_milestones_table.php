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
        Schema::table('event_milestones', function (Blueprint $table) {
            $table->string('calendar_logo')->nullable();
            $table->string('calendar_team_logo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_milestones', function (Blueprint $table) {
            //
        });
    }
};
