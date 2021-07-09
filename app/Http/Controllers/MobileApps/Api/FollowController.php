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
        $user=$request->user;
        $profile=Customer::findOrFail($id);

        $followers=Customer::join('followers', 'customers.id', '=', 'followers.follower_id')
            ->select('customers.id', 'name', 'image', 'username')
            ->orderBy('customers.id', 'desc')
            ->where('followers.customer_id', $profile->id)
            ->paginate(env('PAGE_RESULT_COUNT'));

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('followers')
        ];
    }

    public function followings(Request $request, $id){
        $user=$request->user;
        $profile=Customer::findOrFail($id);

        $followings=Customer::join('followers', 'customers.id', '=', 'followers.customer_id')
            ->select('customers.id', 'name', 'image', 'username')
            ->orderBy('customers.id', 'desc')
            ->where('followers.follower_id', $profile->id)
            ->paginate(env('PAGE_RESULT_COUNT'));

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('followings')
        ];
    }

}
