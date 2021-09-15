<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = [
        'name',
         'description',
         'map_count',
         'reduce_count',
        ];
        // protected $hidden = [
        // 'created_at', 'updated_at','id'
        // ];


        public function tasks(){
            return $this->hasMany(Task::class);
        }

        public function data(){
            return $this->hasMany(Data::class);
        }

        public function owner_jobs(){
            return $this->hasMany(Ownerjob::class);
        }
}
