<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Job;
    

    class JobController extends Controller{
        function listJobs(){
            //list jobs which has owner job not done 
            $jobs = Job::whereHas('owner_jobs',function($query){
                $query->whereIn('status',['init','mapping','reducing']);
            })->select(['id','name','description'])->get();

            return ['jobList'=>$jobs];

        }

        function add(){
            
        }
    }
