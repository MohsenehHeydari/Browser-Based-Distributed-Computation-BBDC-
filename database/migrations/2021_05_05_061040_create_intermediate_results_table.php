<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntermediateResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('intermediate_results', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('data_id');
            $table->integer('task_id');
            $table->integer('owner_job_id');
            $table->string('key');
            $table->string('value')->nullable();
            $table->string('value_url')->nullable(); //if value is big it can be saved in another file
            $table->enum('status',['init','pending','done','fail']);
            $table->string('description')->nullable();
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
        Schema::dropIfExists('intermediate_results');
    }
}
