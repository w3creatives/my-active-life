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
        Schema::create('datasource_point_trackers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('data_source_id')->nullable()->index('index_datasource_point_trackers_on_data_source_id');
            $table->date('date')->nullable()->index('index_datasource_point_trackers_on_date');
            $table->float('total_point')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datasource_point_trackers');
    }
};
