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
        Schema::create('fit_life_invitations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('inviter_id')->index('index_fit_life_invitations_on_inviter_id');
            $table->integer('invitee_id')->nullable();
            $table->string('invitee_email');
            $table->bigInteger('activity_id')->index('index_fit_life_invitations_on_activity_id');
            $table->string('invitation_message')->nullable();
            $table->string('secret')->unique('index_fit_life_invitations_on_secret');
            $table->boolean('accepted');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fit_life_invitations');
    }
};
