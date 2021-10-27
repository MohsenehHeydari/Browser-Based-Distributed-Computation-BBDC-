<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('worker_id');
            $table->integer('device_id');
            $table->integer('owner_job_id');
            $table->integer('task_count');
            $table->integer('result_count');
            $table->integer('success_percent');
            $table->integer('avg_processing_duration');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_logs');
    }
}
