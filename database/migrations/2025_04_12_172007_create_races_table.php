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
        Schema::create('races', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index('index_races_on_name');
            $table->text('description')->nullable();
            $table->date('start_date')->default('1970-01-01');
            $table->date('end_date')->default('1971-01-01');
            $table->float('total_points')->default(0);
            $table->string('virtual_race_type')->nullable()->default('rte');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->bigInteger('event_id')->nullable()->index('index_races_on_event_id');
            $table->string('social_hashtags')->nullable();
        });
        // DB::statement("alter table \"races\" add column \"virtual_race_type\" t_virtual_race_type null default 'rte'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
