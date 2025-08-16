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
        Schema::table('active_storage_variant_records', function (Blueprint $table) {
            $table->foreign(['blob_id'], 'fk_rails_993965df05')->references(['id'])->on('active_storage_blobs')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('active_storage_variant_records', function (Blueprint $table) {
            $table->dropForeign('fk_rails_993965df05');
        });
    }
};
