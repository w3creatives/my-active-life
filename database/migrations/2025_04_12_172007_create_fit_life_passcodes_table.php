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
        Schema::create('fit_life_passcodes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('passcode')->index('index_fit_life_passcodes_on_passcode');
            $table->integer('grantee')->nullable()->index('index_fit_life_passcodes_on_grantee');
            $table->integer('referral_count')->default(0);
            $table->json('data');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['passcode', 'grantee'], 'index_fit_life_passcodes_on_passcode_and_grantee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_life_passcodes');
    }
};
