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
        Schema::table('mailboxer_receipts', function (Blueprint $table) {
            $table->foreign(['notification_id'], 'receipts_on_notification_id')->references(['id'])->on('mailboxer_notifications')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailboxer_receipts', function (Blueprint $table) {
            $table->dropForeign('receipts_on_notification_id');
        });
    }
};
