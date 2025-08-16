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
        Schema::create('points_totals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('amount')->nullable();
            $table->bigInteger('user_id')->nullable()->index('index_points_totals_on_user_id');
            $table->bigInteger('event_id')->nullable()->index('index_points_totals_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['event_id', 'user_id'], 'index_points_totals_on_event_id_and_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_totals');
    }
};
