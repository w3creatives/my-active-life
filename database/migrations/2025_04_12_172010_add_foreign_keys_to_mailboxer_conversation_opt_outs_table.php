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
        Schema::table('mailboxer_conversation_opt_outs', function (Blueprint $table) {
            $table->foreign(['conversation_id'], 'mb_opt_outs_on_conversations_id')->references(['id'])->on('mailboxer_conversations')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailboxer_conversation_opt_outs', function (Blueprint $table) {
            $table->dropForeign('mb_opt_outs_on_conversations_id');
        });
    }
};
