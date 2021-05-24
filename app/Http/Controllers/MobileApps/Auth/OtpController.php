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
            'mobile'=>'required|string|digits:10|exists:customers',
            'otp'=>'required|digits:6'
        ]);

        switch($request->type){
            case 'login': return $this->verifyLogin($request);
            case 'reset': return $this->verifyResetPassword($request);
        }

        return [
            'status'=>'failed',
            'message'=>'Request is not valid'
        ];
    }


    protected function verifyLogin(Request $request){
        $user=Customer::where('mobile', $request->mobile)->first();
        if(in_array($user->status, [0,1])){
            if(OTPModel::verifyOTP('customer',$user->id,$request->type,$request->otp)){
                $user->notification_token=$request->notification_token;
                $user->status=1;
                $user->save();

                $token=Auth::guard('customerapi')->fromUser($user);

                return [
                    'status'=>'success',
                    'message'=>'otp_verified',
                    'display'=>'OTP has been verified successfully',
                    'data'=>compact('token')
                ];
            }

            $token=Auth::guard('customerapi')->fromUser($user);

            return [
                'status'=>'failed',
                'message'=>'incorrect_otp',
                'display'=>'OTP is not correct',
                'data'=>[]
            ];

        }
        $token=Auth::guard('customerapi')->fromUser($user);

        return [
            'status'=>'success',
            'message'=>'otp_verified',
            'display'=>'OTP has been verified successfully',
            'data'=>compact('token')
        ];
    }


    protected function verifyResetPassword(Request $request){
        $user=Customer::where('mobile', $request->mobile)->first();
        if(in_array($user->status, [0,1])){
            if(OTPModel::verifyOTP('customer',$user->id,$request->type,$request->otp)){

                $user->status=1;
                $user->save();

                $token=Auth::guard('customerapi')->fromUser($user);

                return [
                    'status'=>'success',
                    'message'=>'otp_verified',
                    'display'=>'OTP has been verified successfully',
                    'data'=>compact('token')
                ];
            }

            return [
                'status'=>'failed',
                'message'=>'incorrect_otp',
                'display'=>'OTP is not correct',
                'data'=>[]
            ];

        }
        return [
            'status'=>'failed',
            'message'=>'account_blocked',
            'display_message'=>'This account has been blocked',
            'data'=>[]
        ];
    }


    public function resend(Request $request){
        $request->validate([
            'type'=>'required|string|max:15',
            'mobile'=>'required|string|digits:10|exists:customers',
        ]);

        $user=Customer::where('mobile', $request->mobile)->first();
        if(in_array($user->status, [0,1])){
                $otp=OTPModel::createOTP('customer', $user->id, $request->type);
                $msg=str_replace('{{otp}}', $otp, config('sms-templates.'.$request->type));
                event(new SendOtp($user->mobile, $msg));
                return [
                    'status'=>'success',
                    'message'=>'Please verify OTP to continue',
                ];
        }

        return [
            'status'=>'failed',
            'message'=>'Account has been blocked',
        ];

    }

}
