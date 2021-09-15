<?php

use Carbon\Carbon;
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Models\OwnerJob::class, function (Faker\Generator $faker) {

    return [
        'owner_id'=>1,
        'job_id'=>1,
        'data_url'=>$faker->name,
        'data_count'=>random_int(2,20),
        'expire_date'=>Carbon::now()->addDays(random_int(1,5)),
        'reduced_count'=>0,
        'mapped_count'=>0,
    ];
});

$factory->define(App\Models\Data::class, function (Faker\Generator $faker,$owner){
    return [
        'task_id'=>1,
        'owner_job_id'=>function () {
            return factory(App\Models\OwnerJob::class)->create()->id;
        },
        'worker_id'=>1,
        'device_id'=> null,
        'url'=>'data/0'.random_int(1,4).'.json',
        'status'=>'init',
        'description'=> '',
    ];
});

