<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessLog extends Model

{
    protected $fillable = [
         'worker_id',
         'device_id',
         'owner_job_id',
         'task_count',
         'result_count',
         'success_percent',
         'avg_proccessing_duration',
        ];
        // protected $hidden = [
        // 'created_at', 'updated_at','id'
        // ];


}

