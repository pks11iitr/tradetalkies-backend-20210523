<?php

namespace App\Http\Controllers\MobileApps\Auth;

use App\Models\Customer;
use App\Models\OTPModel;
use App\Services\SMS\Msg91;
use App\Services\SMS\Nimbusit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function forgot(Request $request){
        $request->validate([
            'user_id' => userId($request)=='email'?('required|email|string|exists:customers,email'):('required|digits:10|string|exists:customers,username'),
        ], ['user_id.exists'=>'This account is not registered with us. Please signup to continue']);

        $customer=Customer::getCustomer($request);
        if(!$customer){
            return [
                'status'=>'failed',
                'message'=>'not_registered',
                'display_message'=>'This account is not registered with us',
                'data'=>[]
            ];
        }
        if(!in_array($customer->status, [0,1])){
            return [
                'status'=>'failed',
                'message'=>'account_blocked',
                'display_message'=>'This account has been blocked',
                'data'=>[]
            ];
        }
        $otp=OTPModel::createOTP('customer', $customer->id, 'reset');
//        $msg=str_replace('{{otp}}', $otp, config('sms-templates.reset'));
//        Nimbusit::send($customer->mobile,$msg);
        return [
            'status'=>'success',
            'message'=>'otp_verify',
            'display_message'=>'Please verify OTP sent on your email',
            'data'=>[]
        ];
    }


    public function updatePassword(Request $request){

        $user=auth()->guard('customerapi')->user();
        if(!$user){
            return [
                'status'=>'failed',
                'message'=>'invalid_request',
                'display_message'=>'Invalid Request',
                'data'=>[]
            ];
        }

        $user->password=Hash::make($request->password);
        $user->save();

        return [
            'status'=>'success',
            'message'=>'password_updated',
            'display_message'=>'Password Has Been Updated Successfully. Please log in to continue.',
            'data'=>[]
        ];

    }

}
