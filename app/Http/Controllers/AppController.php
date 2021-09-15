<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller{

    public  function setCookie(Request $request){
        $name=$request->name;
        $value=$request->value;
        $expire_days=$request->expire_days;

        $expire_days=$expire_days*60*24;
        \Cookie::queue($name, $value, $expire_days);
        return ['message'=>'cookie set successfully'];

    }

    public  function getCookie($name){
        $value=\Cookie::get($name);
        return response()->json(['value'=>$value]);

    }



}