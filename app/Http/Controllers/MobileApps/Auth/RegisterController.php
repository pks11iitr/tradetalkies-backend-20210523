<?php

namespace App\Http\Controllers\MobileApps\Auth;

use App\Events\CustomerRegistered;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => ['required', 'string', 'min:5', 'max:25'],
            'email'=>['required', 'email', 'max:50'],
            'password'=>'required|min:5|max:20',
        ], [
            'username.max'=>'Username should be less than 20 characters',
            'username.unique'=>'Username already registered. Please log in to continue',
            'email.unique'=>'Email already registered. Please log in to continue',
            'password.*'=>'Password length must be 5 to 20 characters'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return Customer::create([
            'username' => $data['username'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password'])
        ]);
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        if(Customer::where('username', $request->username)->orWhere('email', $request->email)->first()){
            return [
                'status'=>'failed',
                'action'=>'failed',
                'display_message'=>'Username or Email already registered',
                'data'=>[]
            ];
        }

        $user = $this->create($request->all());
        event(new CustomerRegistered($user));

        return [
            'status'=>'success',
            'action'=>'register_success',
            'display_message'=>'Please Verify Email To Continue',
            'data'=>[]
        ];
    }
}
