<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Job;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        // $jobs=Job::get();
        

        // $jobs=\DB::table('jobs')->first();
        // return ($jobs);
        // dd($device_id);
        
        return view('home')->with(['device_id'=>\Cookie::get('device-id')]); // with a variable [name => value] : you can access via key name as a variable
    }
}
