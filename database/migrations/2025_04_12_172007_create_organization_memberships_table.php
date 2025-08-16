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
        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('organization_id')->nullable()->index('index_organization_memberships_on_organization_id');
            $table->bigInteger('user_id')->nullable()->index('index_organization_memberships_on_user_id');
            $table->boolean('admin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_memberships');
    }
};
