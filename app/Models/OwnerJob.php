<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerJob extends Model
{
    protected $fillable = [
        'name',
        'owner_id',
        'job_id',
        'data_url',
        'data_count',
        'expire_date',
        'reduced_count',
        'mapped_count',
        'status',
        'final_result',
        'final_result_url',
        'proccess_log,'

    ];

    public function job(){
        return $this->belongsTo(job::class);
    }

    public function owner(){
        return $this->belongsTo(User::class,'user_id','id');
    }
}
