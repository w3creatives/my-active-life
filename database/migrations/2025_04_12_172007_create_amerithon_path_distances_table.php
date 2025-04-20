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
        Schema::create('amerithon_path_distances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('distance')->nullable()->default(0);
            $table->json('coordinates')->nullable()->default('{}');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['distance'], 'index_amerithon_path_distances_on_distance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amerithon_path_distances');
    }
};
