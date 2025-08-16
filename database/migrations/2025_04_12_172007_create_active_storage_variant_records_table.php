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
        Schema::create('active_storage_variant_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('blob_id');
            $table->string('variation_digest');

            $table->unique(['blob_id', 'variation_digest'], 'index_active_storage_variant_records_uniqueness');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_storage_variant_records');
    }
};
