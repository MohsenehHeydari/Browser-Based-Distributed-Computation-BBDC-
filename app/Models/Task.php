<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'name', 'type','job_id','content'
        ];
        protected $hidden = [
        'created_at', 'updated_at','id'
        ];


        public function job(){
            return $this->belongsTo(Job::class);
        }
}
