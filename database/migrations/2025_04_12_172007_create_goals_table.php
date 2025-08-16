<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('start_date');
            $table->date('end_date');
            $table->float('distance')->nullable();
            $table->integer('time')->nullable();
            $table->integer('duration')->nullable();
            $table->string('measurement')->nullable()->default('time');
            $table->bigInteger('user_id')->nullable()->index('index_goals_on_user_id');
        });
        // DB::statement("alter table \"goals\" add column \"measurement\" t_goal_type null default 'time'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
