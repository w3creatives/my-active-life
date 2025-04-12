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
        Schema::table('fit_exceptions', function (Blueprint $table) {
            $table->foreign(['fit_id'], 'fk_rails_f5d016d5b1')->references(['id'])->on('fits')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fit_exceptions', function (Blueprint $table) {
            $table->dropForeign('fk_rails_f5d016d5b1');
        });
    }
};
