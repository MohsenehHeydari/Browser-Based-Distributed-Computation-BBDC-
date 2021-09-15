<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\Device;

    class RegisterController extends Controller{

        
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

        function addWorkerDevice(Request $req){
            $user=\Auth::user(); // current logined user
            $req->merge(
                [
                    'worker_id'=>$user->id
                ]);
             Device::create($req->all());
            return ['message'=>'device added successfully'];

        }

        function selectDevice(){
            // $user=\App\Models\User::first();
            $user=\Auth::user();
            $devices=Device::where('worker_id',$user->id)->select('id','name')->get();
            // Device::where('worker_id','=',$user->id)->get();
            return ['devices'=>$devices];
        }
    }
