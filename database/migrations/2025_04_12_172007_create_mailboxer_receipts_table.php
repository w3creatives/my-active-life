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
        Schema::create('mailboxer_receipts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('receiver_type')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->integer('notification_id')->index('index_mailboxer_receipts_on_notification_id');
            $table->boolean('is_read')->nullable()->default(false);
            $table->boolean('trashed')->nullable()->default(false);
            $table->boolean('deleted')->nullable()->default(false);
            $table->string('mailbox_type', 25)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->boolean('is_delivered')->nullable()->default(false);
            $table->string('delivery_method')->nullable();
            $table->string('message_id')->nullable();

            $table->index(['receiver_id', 'receiver_type'], 'index_mailboxer_receipts_on_receiver_id_and_receiver_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailboxer_receipts');
    }
};
