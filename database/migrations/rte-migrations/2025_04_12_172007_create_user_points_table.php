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
        Schema::create('user_points', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('amount')->default(0);
            $table->date('date')->nullable()->index('index_user_points_on_date');
            $table->bigInteger('user_id')->nullable()->index('index_user_points_on_user_id');
            $table->bigInteger('data_source_id')->nullable()->index('index_user_points_on_data_source_id');
            $table->bigInteger('event_id')->nullable()->index('index_user_points_on_event_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->string('transaction_id')->nullable()->index('index_user_points_on_transaction_id');
            $table->string('note', 100)->nullable();

            $table->index(['date', 'transaction_id'], 'index_user_points_on_date_and_transaction_id');
            $table->index(['event_id', 'user_id'], 'index_user_points_on_event_id_and_user_id');
            $table->index(['event_id', 'user_id', 'date'], 'index_user_points_on_event_id_and_user_id_and_date');
        });
        DB::statement("alter table \"user_points\" add column \"modality\" modality null default 'run'");
        DB::statement("create index \"index_user_points_on_user_id_and_date_and_modality\" on \"user_points\" (\"user_id\", \"date\", \"modality\")");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_points');
    }
};
