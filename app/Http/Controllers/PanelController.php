<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PanelController extends Controller{

 public function index($type){

    if(!in_array($type,['worker','owner','admin'])){
        throw new \Exception('type is not valid! type is: '.$type);
    }
    $device_id = \Cookie::get('device-id');
    // dd(\Auth::user());
    $data=[
        'device_id'=> $device_id == null ? 0 : $device_id,
        'type'=>$type,
        'user'=>json_encode(\Auth::user())
    ];
    // dd($data);
    return view('dashboard')->with($data);

 }

}