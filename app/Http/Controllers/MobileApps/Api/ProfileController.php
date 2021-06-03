<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Wallet;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(){
        $user=auth()->guard('customerapi')->user();

        if(!$user){
            return [
                'status'=>'failed',
                'message'=>'Please Login to Continue..',
            ];
        }
        $balance = Wallet::balance($user->id);
        $userdata=array(
            'id'=>$user->id,
            'name'=>$user->name,
            'email'=>$user->email,
            'mobile'=>$user->mobile,
            'image'=>$user->image,
            'balance'=>$balance??0,
        );

        return [
            'status'=>'success',
            'message'=>'success',
            'data'=>$userdata,
        ];
    }
    public function update(Request $request){
        $user=auth()->guard('customerapi')->user();
        if(!$user){
            return [
                'status'=>'failed',
                'message'=>'Please Login to Continue..',
            ];
        }
        $request->validate([
            'name'=>'required|string',
            'email'=>'string'
        ]);

        if($request->email && Customer::where('email', $request->email)->where('id', '!=', $user->id)->first())
            return [
                'status'=>'failed',
                'message'=>'Email already register with other user',
            ];

        $user->name=$request->name;
        $user->email=$request->email;
        if($request->image){
            $user->saveImage($request->image, 'customers');
        }
        if($user->save()){
            return [
                'status'=>'success',
                'message'=>'Profile Updated Successfully',
            ];
        }else{
            return [
                'status'=>'failed',
                'message'=>'Profile Not Update',
            ];
        }

    }

    public function getNotificationSettings(Request $request){
        $user=$request->user;
        $user=$user->only('push_likes','push_mentions', 'push_direct_messages', 'push_follows','push_watchlists', 'push_rooms','email_likes', 'email_mentions','email_direct_messages', 'email_follows', 'email_watchlist','email_rooms');
        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'user')
        ];

    }

    public function setNotificationSettings(Request $request){
        $request->validate([
            'push_likes'=>'required|in:0,1',
            'push_mentions'=>'required|in:0,1',
            'push_direct_messages'=>'required|in:0,1',
            'push_follows'=>'required|in:0,1',
            'push_watchlists'=>'required|in:0,1',
            'push_rooms'=>'required|in:0,1',
            'email_likes'=>'required|in:0,1',
            'email_mentions'=>'required|in:0,1',
            'email_direct_messages'=>'required|in:0,1',
            'email_follows'=>'required|in:0,1',
            'email_watchlist'=>'required|in:0,1',
            'email_rooms'=>'required|in:0,1'
        ]);

        $user=$request->user;

        $user->update($request->only('push_likes','push_mentions', 'push_direct_messages', 'push_follows','push_watchlists', 'push_rooms','email_likes', 'email_mentions','email_direct_messages', 'email_follows', 'email_watchlist','email_rooms'));

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'Settings have Been Updated',
            'data'=>[]
        ];

    }

}
