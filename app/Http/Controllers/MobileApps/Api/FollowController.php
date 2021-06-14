<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function follow(Request $request, $id){
        $user=$request->user;
        $profile=Customer::findOrFail($id);
        if($user->id==$profile->id)
            return [
                'status'=>'failed',
                'action'=>'failed',
                'display_message'=>'Cannot follow own profile',
                'data'=>[]
            ];
        if(!$user->followings()->where('customers.id', $profile->id)->first())
            $user->followings()->attach($profile->id);
        else
            $user->followings()->detach($profile->id);
        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>[]
        ];

    }

    public function followers(Request $request, $id){

    }

    public function followings(Request $request, $id){

    }

}
