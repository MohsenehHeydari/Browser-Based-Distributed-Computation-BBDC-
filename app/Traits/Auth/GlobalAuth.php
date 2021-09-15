<?php

namespace App\Traits\Auth;
use Illuminate\Support\Facades\Auth;

trait GlobalAuth
{
    protected $current_panel;

    protected function showForm($component, $data = [])
    {
        return view('auth')->with([
            'data' => json_encode($data),
            'component' => $component
        ]);
    }

    protected function redirectTo($quest = false)
    {
        $path =  '/panel/'.request()->route('type');
        if ($quest !== false) {
            $path = request()->route('type').'/login';
        }
        return $path;
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }
}
