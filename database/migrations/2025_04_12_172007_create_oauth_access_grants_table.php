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
        Schema::create('oauth_access_grants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('resource_owner_id')->index('index_oauth_access_grants_on_resource_owner_id');
            $table->bigInteger('application_id')->index('index_oauth_access_grants_on_application_id');
            $table->string('token')->unique('index_oauth_access_grants_on_token');
            $table->integer('expires_in');
            $table->text('redirect_uri');
            $table->timestamp('created_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('scopes')->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_access_grants');
    }
};
