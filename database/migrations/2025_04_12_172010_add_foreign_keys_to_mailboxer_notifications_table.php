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
        Schema::table('mailboxer_notifications', function (Blueprint $table) {
            $table->foreign(['conversation_id'], 'notifications_on_conversation_id')->references(['id'])->on('mailboxer_conversations')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailboxer_notifications', function (Blueprint $table) {
            $table->dropForeign('notifications_on_conversation_id');
        });
    }
};
