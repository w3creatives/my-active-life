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
        Schema::table('fit_life_invitations', function (Blueprint $table) {
            $table->foreign(['activity_id'], 'fk_rails_ce08bdf4a5')->references(['id'])->on('fit_life_activities')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['inviter_id'], 'fk_rails_dc3ba25db5')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fit_life_invitations', function (Blueprint $table) {
            $table->dropForeign('fk_rails_ce08bdf4a5');
            $table->dropForeign('fk_rails_dc3ba25db5');
        });
    }
};
