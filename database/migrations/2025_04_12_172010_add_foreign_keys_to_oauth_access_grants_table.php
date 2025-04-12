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
        Schema::table('oauth_access_grants', function (Blueprint $table) {
            $table->foreign(['resource_owner_id'], 'fk_rails_330c32d8d9')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['application_id'], 'fk_rails_b4b53e07b8')->references(['id'])->on('oauth_applications')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_access_grants', function (Blueprint $table) {
            $table->dropForeign('fk_rails_330c32d8d9');
            $table->dropForeign('fk_rails_b4b53e07b8');
        });
    }
};
