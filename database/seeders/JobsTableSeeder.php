<?php

namespace Database\Seeders;

use App\Models\Job;
use Illuminate\Database\Seeder;

class JobsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected $data=[
        [
            'name'=>'wordCount',
            'description'=>'this is an example of word count.',
            'map_count'=>'1',
            'reduce_count'=>'1',
        ],
        [
            'name'=>'matrixMultiplication',
            'description'=>'this is an example of matrix multiplication.',
            'map_count'=>'1',
            'reduce_count'=>'1',
        ],
    ];
    public function run()
    {
        foreach($this->data as $item){
            Job::create($item);
        }

    }
}
