<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Auth\GlobalAuth;
use Illuminate\Http\Request;use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use GlobalAuth;

    protected $department;
    protected $lang;

    public function __construct()
    {

        $this->middleware('guest')->except('logout');
    }

    public function showRegistrationForm()
    {
        return $this->showForm('register');
    }

    public function register(Request $request)
    {

        $this->validator($request->all())->validate();


        $user = $this->create($request->all());
        $this->guard()->login($user);
        return response()->json(['redirect_path' => $this->redirectTo()]);

    }

    protected function validator(array $data)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'agree' => 'required',
        ];
        return Validator::make($data, $rules);
    }

    protected function create(array $data)
    {
        try {
            $user = \DB::transaction(function () use ($data) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),
                    'status' => 1,
                ]);

                return $user;
            });

            return $user;

        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
