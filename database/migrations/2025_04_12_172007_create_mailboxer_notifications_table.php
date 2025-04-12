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
        Schema::create('mailboxer_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable()->index('index_mailboxer_notifications_on_type');
            $table->text('body')->nullable();
            $table->string('subject')->nullable()->default('');
            $table->string('sender_type')->nullable();
            $table->integer('sender_id')->nullable();
            $table->integer('conversation_id')->nullable()->index('index_mailboxer_notifications_on_conversation_id');
            $table->boolean('draft')->nullable()->default(false);
            $table->string('notification_code')->nullable();
            $table->string('notified_object_type')->nullable();
            $table->integer('notified_object_id')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamp('updated_at');
            $table->timestamp('created_at');
            $table->boolean('global')->nullable()->default(false);
            $table->timestamp('expires')->nullable();

            $table->index(['notified_object_id', 'notified_object_type'], 'index_mailboxer_notifications_on_notified_object_id_and_type');
            $table->index(['sender_id', 'sender_type'], 'index_mailboxer_notifications_on_sender_id_and_sender_type');
            $table->index(['notified_object_type', 'notified_object_id'], 'mailboxer_notifications_notified_object');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailboxer_notifications');
    }
};
