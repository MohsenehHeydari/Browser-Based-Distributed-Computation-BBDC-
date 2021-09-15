<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntermediateResult extends Model
{
    protected $fillable = [
        'data_id',
        'task_id',
        'owner_job_id',
        'key',
        'value',
        'value_url',
        'status',
        'description'
    ];
}
