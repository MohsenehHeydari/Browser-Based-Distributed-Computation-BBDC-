<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'worker_id',
        'name',
        'CPU',
        'GPU',
        'RAM',
        'battery',
        'availability',
        ];
}
