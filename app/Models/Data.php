<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    protected $fillable = [
        'task_id',
        'owner_job_id',
        'worker_id',
        'device_id',
        'url',
        'status',
        'description',
    ];
    
    public function owner_job(){
        return $this->belongsTo(OwnerJob::class);
    }
    
    public function task(){
        return $this->belongsTo(Task::class);
    }
}
