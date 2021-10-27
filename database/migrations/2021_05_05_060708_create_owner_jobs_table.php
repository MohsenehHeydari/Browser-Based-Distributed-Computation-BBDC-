<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner_id');
            $table->integer('job_id');
            $table->string('name');
            $table->string('data_url')->nullable();
            $table->text('data_links')->nullable();
            $table->integer('data_count'); //count of decomposed data
            $table->timestamp('expire_date');
            $table->integer('reduced_count')->nullable(); // count of reduced task which is done
            $table->integer('mapped_count')->nullable(); // count of mapped task which is done
            $table->enum('status',['init', 'pending', 'done', 'failed', 'waiting', 'mapping', 'reducing']);
            $table->string('final_result')->nullable();
            $table->string('final_result_url')->nullable();
            $table->text('process_log')->nullable(); // log files and details of proccess like changing status
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
        Schema::dropIfExists('owner_jobs');
    }
}
