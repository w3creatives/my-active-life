<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->default('')->fulltext('idx_users_email_gin');
            $table->string('encrypted_password')->default('$2a$04$CKLhHUx/OPhtr0pnHD5XGeLaaXoPJZawF/JuWNsaqxyHG0COqV/6a');
            $table->string('reset_password_token')->nullable()->unique('index_users_on_reset_password_token');
            $table->timestamp('reset_password_sent_at')->nullable();
            $table->timestamp('remember_created_at')->nullable();
            $table->integer('sign_in_count')->default(0);
            $table->timestamp('current_sign_in_at')->nullable();
            $table->timestamp('last_sign_in_at')->nullable();
            $table->ipAddress('current_sign_in_ip')->nullable();
            $table->ipAddress('last_sign_in_ip')->nullable();
            $table->string('first_name')->fulltext('idx_users_first_name_gin');
            $table->string('last_name')->fulltext('idx_users_last_name_gin');
            $table->string('display_name')->nullable();
            $table->boolean('super_admin')->nullable()->default(false);
            $table->integer('preferred_event_id')->nullable();
            $table->date('birthday')->nullable()->index('index_users_on_birthday');
            $table->string('telephone')->nullable();
            $table->text('bio')->nullable();
            $table->string('time_zone')->nullable()->default('Mountain Time (US & Canada)');
            $table->string('street_address1')->nullable();
            $table->string('street_address2')->nullable();
            $table->string('city')->nullable()->index('index_users_on_city');
            $table->string('state')->nullable()->index('index_users_on_state');
            $table->string('country')->nullable()->index('index_users_on_country');
            $table->string('zipcode')->nullable()->index('index_users_on_zipcode');
            $table->timestamp('announcements_last_read_at')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->timestamp('broadcasts_last_read_at')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->string('avatar_type')->nullable();
            $table->string('gender', 100)->nullable()->default('unknown');
            $table->string('shirt_size', 100)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->unique(['email'], 'index_users_on_email');
        });
        //DB::statement("alter table \"users\" add column \"gender\" gender null default 'unknown'");
        //DB::statement("alter table \"users\" add column \"shirt_size\" shirt_size null");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
