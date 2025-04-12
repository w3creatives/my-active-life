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
        Schema::create('oauth_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('uid')->unique('index_oauth_applications_on_uid');
            $table->string('secret');
            $table->text('redirect_uri');
            $table->string('scopes')->default('');
            $table->boolean('confidential')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_applications');
    }
};
