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
        Schema::table('fit_participations', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_rails_47727163d0')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['fit_id'], 'fk_rails_80bf5df340')->references(['id'])->on('fits')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fit_participations', function (Blueprint $table) {
            $table->dropForeign('fk_rails_47727163d0');
            $table->dropForeign('fk_rails_80bf5df340');
        });
    }
};
