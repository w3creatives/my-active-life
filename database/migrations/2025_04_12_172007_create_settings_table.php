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
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('var');
            $table->text('value')->nullable();
            $table->integer('thing_id')->nullable();
            $table->string('thing_type', 30)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->unique(['thing_type', 'thing_id', 'var'], 'index_settings_on_thing_type_and_thing_id_and_var');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
