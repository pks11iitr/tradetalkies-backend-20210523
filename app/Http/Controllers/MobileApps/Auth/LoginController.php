<?php

namespace App\Http\Controllers\MobileApps\Auth;

use App\Events\SendOtp;
use App\Models\Customer;
use App\Models\OTPModel;
use App\Services\SMS\Msg91;
use App\Services\SMS\Nimbusit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */


    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'user_id' => userId($request)=='email'?('required|email|string|exists:customers,email'):('required|string|exists:customers,username'),
            'password' => 'required|string',
        ], ['user_id.exists'=>'This account is not registered with us. Please signup to continue']);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        //$this->validateLogin($request);
        $user=Customer::where('username', $request->user_id)
            ->orWhere('email', $request->user_id)
            ->first();

        if(!$user){
            return [
                'status'=>'failed',
                'action'=>'not_registered',
                'display_message'=>'This account is not registered with us',
                'data'=>[]
            ];
        }

        if ($token=$this->attemptLogin($request)) {
            //$user=Customer::getCustomer($request);
            $user->notification_token=$request->notification_token;
            $user->save();
            return $this->sendLoginResponse($user, $token);
        }
        return [
            'status'=>'failed',
            'action'=>'credentials_incorrect',
            'display_message'=>'Credentials are not correct',
            'data'=>[]
        ];

    }

    protected function attemptLogin(Request $request)
    {
        return Auth::guard('customerapi')->attempt(
            [userId($request)=>$request->user_id, 'password'=>$request->password]
        );
    }

    protected function sendLoginResponse($user, $token){
        if($user->status==0){
            $otp=OTPModel::createOTP('customer', $user->id, 'login');
            //$msg=str_replace('{{otp}}', $otp, config('sms-templates.login'));
            //Nimbusit::send($user->mobile,$msg);
            return [
                'status'=>'success',
                'action'=>'otp_verify',
                'display_message'=>'Please verify OTP sent on your email',
                'data'=>[]
            ];
        }
        else if($user->status==1)
            return [
                'status'=>'success',
                'action'=>'login_successful',
                'display_message'=>'Login Successful',
                'data'=>compact('token')
            ];
        else
            return [
                'status'=>'failed',
                'action'=>'account_blocked',
                'display_message'=>'This account has been blocked',
                'data'=>[]
            ];
    }


    /**
     * Handle a login request to the application with otp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function googleLogin(Request $request){
        $request->validate([
            'google_token'=>'required',
            'notification_token'=>'required',
        ]);

        $client= new \Google_Client(['client_id'=>env('GOOGLE_WEB_CLIENT_ID')]);
        $payload = $client->verifyIdToken($request->google_token);
        if (!isset($payload['email'])) {
            return [
                'status'=>'failed',
                'action'=>'invalid_token',
                'display_message'=>'Invalid Token Request',
                'data'=>[]
            ];
        }
        $email=$payload['email'];
        $name=$payload['name']??'';
        $picture=$payload['picture']??'';

        $user=Customer::where('email', $email)->first();
        if(!$user){
            $user=Customer::create([
                'name'=>$name,
                'email'=>$email,
                'email_verified_at'=>date('Y-m-d H:i:s'),
                'username'=>'TTK'.time(),
                'password'=>'none',
                'status'=>1
            ]);
        }

        if(!in_array($user->status, [0,1]))
            return [
                'status'=>'failed',
                'action'=>'account_blocked',
                'display_message'=>'This account has been blocked',
                'data'=>[]
            ];


        $user->notification_token=$request->notification_token;
        $user->save();

        $token=Auth::guard('customerapi')->fromUser($user);

        return [
            'status'=>'success',
            'action'=>'otp_verified',
            'display_message'=>'OTP has been verified successfully',
            'data'=>compact('token')
        ];


    }

    public function facebookLogin(Request $request){
        $user=Socialite::driver('facebook')->user();


        if (!isset($user->email)) {
            return [
                'status'=>'failed',
                'action'=>'invalid_token',
                'display_message'=>'Invalid Token Request',
                'data'=>[]
            ];
        }
        $email=$user->email;
        $name=$user->name??'';
        //$picture=$payload['picture']??'';

        $user=Customer::where('email', $email)->first();
        if(!$user){
            $user=Customer::create([
                'name'=>$name,
                'email'=>$email,
                'email_verified_at'=>date('Y-m-d H:i:s'),
                'username'=>'TTK'.time(),
                'password'=>'none',
                'status'=>1
            ]);
        }

        if(!in_array($user->status, [0,1]))
            return [
                'status'=>'failed',
                'action'=>'account_blocked',
                'display_message'=>'This account has been blocked',
                'data'=>[]
            ];


        $user->notification_token=$request->notification_token;
        $user->save();

        $token=Auth::guard('customerapi')->fromUser($user);

        return [
            'status'=>'success',
            'action'=>'otp_verified',
            'display_message'=>'OTP has been verified successfully',
            'data'=>compact('token')
        ];


    }

    public function loginCheck(Request $request){
        $user=auth()->guard('customerapi')->user();
        if($user)
            return [
                'status'=>'success',
                'action'=>'logged_in',
                'display_message'=>'',
                'data'=>[]
            ];

        return [
            'status'=>'failed',
            'action'=>'log_out',
            'display_message'=>'',
            'data'=>[]
        ];

    }


    public function logout(Request $request){
        $user=$request->user;
        $user->notification_token=null;
        $user->save();
        return [
            'status'=>'success',
            'action'=>'logout_success',
            'display_message'=>'User has been logged out',
            'data'=>[]
        ];
    }

}
