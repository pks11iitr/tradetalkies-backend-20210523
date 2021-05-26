<?php

namespace App\Http\Controllers\MobileApps\Auth;

use App\Events\SendOtp;
use App\Models\Customer;
use App\Models\OTPModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{

    /**
     * Handle a login request to the application with otp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function verify(Request $request){
        $request->validate([
            'type'=>'required|string|max:15',
            'user_id' => userId($request)=='email'?('required|email|string|exists:customers,email'):('required|string|exists:customers,username'),
            'otp'=>'required|digits:6'
        ],['user_id.*'=>'This account is not registered with us.']);

        switch($request->type){
            case 'login': return $this->verifyLogin($request);
            case 'reset': return $this->verifyResetPassword($request);
        }

        return [
            'status'=>'failed',
            'action'=>'invalid_request',
            'display_message'=>'Invalid Request',
            'data'=>[]
        ];
    }


    protected function verifyLogin(Request $request){
        $user=Customer::where(userId($request), $request->user_id)->first();
        if(in_array($user->status, [0,1])){
            if(OTPModel::verifyOTP('customer',$user->id,$request->type,$request->otp)){
                $user->notification_token=$request->notification_token;
                $user->status=1;
                $user->save();

                $token=Auth::guard('customerapi')->fromUser($user);

                return [
                    'status'=>'success',
                    'action'=>'otp_verified',
                    'display_message'=>'OTP has been verified successfully',
                    'data'=>compact('token')
                ];
            }

            $token=Auth::guard('customerapi')->fromUser($user);

            return [
                'status'=>'failed',
                'action'=>'incorrect_otp',
                'display_message'=>'OTP is not correct',
                'data'=>[]
            ];

        }
        $token=Auth::guard('customerapi')->fromUser($user);

        return [
            'status'=>'success',
            'action'=>'otp_verified',
            'display_message'=>'OTP has been verified successfully',
            'data'=>compact('token')
        ];
    }


    protected function verifyResetPassword(Request $request){
        $user=Customer::where(userId($request), $request->user_id)->first();
        if(in_array($user->status, [0,1])){
            if(OTPModel::verifyOTP('customer',$user->id,$request->type,$request->otp)){

                $user->status=1;
                $user->save();

                $token=Auth::guard('customerapi')->fromUser($user);

                return [
                    'status'=>'success',
                    'action'=>'otp_verified',
                    'display_message'=>'OTP has been verified successfully',
                    'data'=>compact('token')
                ];
            }

            return [
                'status'=>'failed',
                'action'=>'incorrect_otp',
                'display_message'=>'OTP is not correct',
                'data'=>[]
            ];

        }
        return [
            'status'=>'failed',
            'action'=>'account_blocked',
            'display_message'=>'This account has been blocked',
            'data'=>[]
        ];
    }


    public function resend(Request $request){
        $request->validate([
            'type'=>'required|string|max:15',
            'user_id' => userId($request)=='email'?('required|email|string|exists:customers,email'):('required|string|exists:customers,username'),
        ], ['user_id.*'=>'This account is not registered with us.']);

        $user=Customer::where(userId($request), $request->user_id)->first();
        if(in_array($user->status, [0,1])){
                $otp=OTPModel::createOTP('customer', $user->id, $request->type);
//                $msg=str_replace('{{otp}}', $otp, config('sms-templates.'.$request->type));
//                event(new SendOtp($user->mobile, $msg));
            return [
                'status'=>'success',
                'action'=>'otp_verify',
                'display_message'=>'OTP Has Been Resent',
                'data'=>[]
            ];
        }

        return [
            'status'=>'failed',
            'action'=>'account_blocked',
            'display_message'=>'This account has been blocked',
            'data'=>[]
        ];

    }

}
