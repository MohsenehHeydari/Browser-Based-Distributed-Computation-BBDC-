<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TasksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected $data = [
        [
            'job_id'=> 1,
            'name'=> 'map',
            'content'=> '',
            'type'=> 'map',
        ],

        [
            'job_id'=> 1,
            'name'=> 'reduce',
            'content'=> "",
            'type'=> 'reduce',
        ],
        [
            'job_id'=> 2,
            'name'=> 'map',
            'content'=> '',
            'type'=> 'map',
        ],

        [
            'job_id'=> 2,
            'name'=> 'reduce',
            'content'=> "",
            'type'=> 'reduce',
        ],

        [
            'job_id'=> 3,
            'name'=> 'map',
            'content'=> '',
            'type'=> 'map',
        ],
        [
            'job_id'=> 4,
            'name'=> 'map',
            'content'=> '',
            'type'=> 'map',
        ],
        [
            'job_id'=> 5,
            'name'=> 'map',
            'content'=> '',
            'type'=> 'map',
        ],
        [
            'job_id'=> 6,
            'name'=> 'map',
            'content'=> '',
            'type'=> 'map',
        ]
    ];
    public function run()
    {
        foreach($this->data as $task ){
            Task::create($task);
        }
    }
}
