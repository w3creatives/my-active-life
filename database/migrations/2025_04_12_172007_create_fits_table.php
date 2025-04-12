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
        Schema::create('fits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index('index_fits_on_name');
            $table->timestamp('start_time')->index('index_fits_on_start_time');
            $table->integer('spots_available')->default(4);
            $table->integer('rsvp_count')->nullable()->default(0);
            $table->text('recurring')->nullable();
            $table->text('description')->nullable();
            $table->string('street_address');
            $table->string('city');
            $table->string('state')->index('index_fits_on_state');
            $table->string('country')->index('index_fits_on_country');
            $table->string('zipcode')->index('index_fits_on_zipcode');
            $table->string('fit_email')->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->boolean('signed_waiver')->nullable()->default(false);
            $table->bigInteger('user_id')->nullable()->index('index_fits_on_user_id');
            $table->bigInteger('event_id')->nullable()->index('index_fits_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['user_id', 'event_id'], 'index_fits_on_user_id_and_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fits');
    }
};
