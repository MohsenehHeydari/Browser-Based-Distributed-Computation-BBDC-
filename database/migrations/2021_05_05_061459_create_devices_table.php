<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->increments('id')->unique();
            $table->integer('worker_id');
            $table->string('info')->nullable(); //device model, browser 
            $table->string('name');
            $table->integer('RAM'); //percent
            $table->integer('CPU'); //percent
            $table->integer('GPU'); //percent
            $table->integer('battery'); // treshold in percent
            $table->integer('availability')->nullable(); // minute
            $table->timestamp('start_date')->nullable(); //time of task request
            $table->timestamp('end_date')->nullable();  //end of available resource
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
        Schema::dropIfExists('devices');
    }
}
