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
        Schema::table('fit_life_activity_registrations', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_rails_358b484510')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['activity_id'], 'fk_rails_ae71f2702b')->references(['id'])->on('fit_life_activities')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fit_life_activity_registrations', function (Blueprint $table) {
            $table->dropForeign('fk_rails_358b484510');
            $table->dropForeign('fk_rails_ae71f2702b');
        });
    }
};
