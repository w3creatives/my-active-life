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
        Schema::create('fit_exceptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('fit_id')->nullable()->index('index_fit_exceptions_on_fit_id');
            $table->timestamp('time')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_exceptions');
    }
};
