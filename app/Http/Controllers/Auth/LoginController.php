<?php

namespace App\Http\Controllers\Auth;

use App\Traits\Auth\AuthenticatesUsers;
use App\Http\Controllers\Controller;
use App\Traits\Auth\GlobalAuth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;
    use GlobalAuth;

    protected $department;
    protected $lang;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');

    }



    public function showLoginForm()
    {
        return $this->showForm('login');
    }

    public function login(Request $request)
    {

        $this->validateLogin($request);

        $this->ensureIsNotRateLimited();

        if ($this->attemptLogin($request)) {

            return $this->sendLoginResponse($request);
        }
        return $this->sendFailedLoginResponse($request);
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return redirect($this->redirectTo(true));
    }

    public function username()
    {
        return 'email';
    }



}
