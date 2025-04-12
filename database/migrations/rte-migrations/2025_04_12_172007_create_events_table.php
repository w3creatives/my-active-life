<?php

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
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index('index_events_on_name');
            $table->string('social_hashtags');
            $table->text('description')->nullable();
            $table->date('start_date')->default('1970-01-01');
            $table->date('end_date')->default('1971-01-01');
            $table->float('total_points')->default(0);
            $table->string('registration_url')->default('#');
            $table->integer('team_size')->default(4);
            $table->bigInteger('organization_id')->nullable()->index('index_events_on_organization_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->integer('supported_modalities')->nullable()->default(0);
            $table->boolean('open')->nullable();
            $table->integer('template')->nullable()->default(1);
            $table->string('logo', 100)->nullable()->default('Logo-Amerithon.png');
            $table->string('bibs_name', 50)->nullable();
            $table->string('event_group', 100)->nullable();
            $table->integer('calendar_days')->nullable();
        });
        DB::statement("alter table \"events\" add column \"event_type\" event_type null default 'regular'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
