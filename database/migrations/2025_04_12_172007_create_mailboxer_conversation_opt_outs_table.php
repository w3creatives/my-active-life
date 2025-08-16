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
        Schema::create('mailboxer_conversation_opt_outs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('unsubscriber_type')->nullable();
            $table->integer('unsubscriber_id')->nullable();
            $table->integer('conversation_id')->nullable()->index('index_mailboxer_conversation_opt_outs_on_conversation_id');

            $table->index(['unsubscriber_id', 'unsubscriber_type'], 'index_mailboxer_conversation_opt_outs_on_unsubscriber_id_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailboxer_conversation_opt_outs');
    }
};
