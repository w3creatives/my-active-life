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
        Schema::table('user_data_sources', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_rails_613f1f5ffd')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_id'], 'fk_rails_91b37f85b2')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['data_source_id'], 'fk_rails_ee7bc8d008')->references(['id'])->on('data_sources')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_data_sources', function (Blueprint $table) {
            $table->dropForeign('fk_rails_613f1f5ffd');
            $table->dropForeign('fk_rails_91b37f85b2');
            $table->dropForeign('fk_rails_ee7bc8d008');
        });
    }
};
