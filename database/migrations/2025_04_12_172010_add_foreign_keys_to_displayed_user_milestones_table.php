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
        Schema::table('displayed_user_milestones', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_rails_3b2d1838a8')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['event_milestone_id'], 'fk_rails_d69096467a')->references(['id'])->on('event_milestones')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('displayed_user_milestones', function (Blueprint $table) {
            $table->dropForeign('fk_rails_3b2d1838a8');
            $table->dropForeign('fk_rails_d69096467a');
        });
    }
};
