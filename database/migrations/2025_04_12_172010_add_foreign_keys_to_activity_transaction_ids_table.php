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
        Schema::table('activity_transaction_ids', function (Blueprint $table) {
            $table->foreign(['event_id'], 'fk_rails_1be907e3f7')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['data_source_id'], 'fk_rails_484eafd88b')->references(['id'])->on('data_sources')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_rails_7623450127')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_transaction_ids', function (Blueprint $table) {
            $table->dropForeign('fk_rails_1be907e3f7');
            $table->dropForeign('fk_rails_484eafd88b');
            $table->dropForeign('fk_rails_7623450127');
        });
    }
};
