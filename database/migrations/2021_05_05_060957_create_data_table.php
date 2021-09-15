<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('task_id')->nullable();
            $table->integer('owner_job_id');
            $table->integer('worker_id')->nullable();
            $table->integer('device_id')->nullable();
            $table->string('url')->nullable();
            $table->enum('status',['init', 'pending', 'done', 'fail', 'waiting']);
            $table->string('description')->nullable(); // information about resources which are used after doing task
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
        Schema::dropIfExists('data');
    }
}
