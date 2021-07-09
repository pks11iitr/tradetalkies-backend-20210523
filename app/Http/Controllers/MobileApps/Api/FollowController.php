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

        $fids=[];
        foreach($followers as $f)
            $fids[]=$f->id;

        if(count($fids)){
            $myfollowings=Customer::join('followers', 'customers.id', '=', 'followers.customer_id')
                ->select('customers.id')
                ->orderBy('customers.id', 'desc')
                ->where('followers.follower_id', $user->id)
                ->whereIn('followers.customer_id', $fids)
                ->get()->map(function($element){
                    return $element->id;
                })->toArray();

            foreach ($followers as $f){
                if(in_array($f->id, $myfollowings))
                    $f->is_following=1;
                else
                    $f->is_following=0;
            }
        }

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


        $fids=[];
        foreach($followings as $f)
            $fids[]=$f->id;

        if(count($fids)){
            if(count($fids)){
                $myfollowings=Customer::join('followers', 'customers.id', '=', 'followers.customer_id')
                    ->select('customers.id')
                    ->orderBy('customers.id', 'desc')
                    ->where('followers.follower_id', $user->id)
                    ->whereIn('followers.customer_id', $fids)
                    ->get()->map(function($element){
                        return $element->id;
                    })->toArray();

                foreach ($followings as $f){
                    if(in_array($f->id, $myfollowings))
                        $f->is_following=1;
                    else
                        $f->is_following=0;
                }
            }
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('followings')
        ];
    }

}
