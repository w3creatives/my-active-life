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
        Schema::create('active_storage_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('record_type');
            $table->bigInteger('record_id');
            $table->bigInteger('blob_id')->index('index_active_storage_attachments_on_blob_id');
            $table->timestamp('created_at');

            $table->unique(['record_type', 'record_id', 'name', 'blob_id'], 'index_active_storage_attachments_uniqueness');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_storage_attachments');
    }
};
