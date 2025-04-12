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
        Schema::create('fit_participations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('fit_id')->nullable()->index('index_fit_participations_on_fit_id');
            $table->bigInteger('user_id')->nullable()->index('index_fit_participations_on_user_id');
            $table->boolean('signed_waiver')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_participations');
    }
};
