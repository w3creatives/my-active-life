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
        Schema::table('user_follows', function (Blueprint $table) {
            $table->foreign(['follower_id'], 'fk_rails_12475d0cec')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['event_id'], 'fk_rails_281c6475ed')->references(['id'])->on('events')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['followed_id'], 'fk_rails_c773a2880d')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_follows', function (Blueprint $table) {
            $table->dropForeign('fk_rails_12475d0cec');
            $table->dropForeign('fk_rails_281c6475ed');
            $table->dropForeign('fk_rails_c773a2880d');
        });
    }
};
