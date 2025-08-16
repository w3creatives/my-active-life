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
        Schema::table('data_source_profiles', function (Blueprint $table) {
            $table->foreign(['data_source_id'], 'fk_rails_14442a9f85')->references(['id'])->on('data_sources')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_rails_5a6c6c830f')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_source_profiles', function (Blueprint $table) {
            $table->dropForeign('fk_rails_14442a9f85');
            $table->dropForeign('fk_rails_5a6c6c830f');
        });
    }
};
