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
        Schema::create('active_storage_blobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique('index_active_storage_blobs_on_key');
            $table->string('filename');
            $table->string('content_type')->nullable();
            $table->text('metadata')->nullable();
            $table->bigInteger('byte_size');
            $table->string('checksum');
            $table->timestamp('created_at');
            $table->string('service_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_storage_blobs');
    }
};
