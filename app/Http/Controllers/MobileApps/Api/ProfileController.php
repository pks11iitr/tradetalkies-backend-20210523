<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Post;
use App\Models\Wallet;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getProfile(){
        $user=auth()->guard('customerapi')->user();

        if(!$user){
            return [
                'status'=>'failed',
                'action'=>'login',
                'display_message'=>'Please login to continue..',
                'data'=>[]
            ];
        }

        $user=$user->only('name', 'username', 'image', 'about', 'telegram_id', 'twitter_id', 'industry_id');

        $industries=config('myconfig.industry');

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('user', 'industries')
        ];
    }
    public function setProfile(Request $request){
        $user=auth()->guard('customerapi')->user();
        if(!$user){
            return [
                'status'=>'failed',
                'action'=>'login',
                'display_message'=>'Please login to continue..',
                'data'=>[]
            ];
        }

        $user->update($request->only('name', 'twitter_id', 'telegram_id', 'industry_id', 'about', 'age'));

        if($request->image)
            $user->saveImage($request->image, 'customer');


        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Please Updated Successfully',
            'data'=>[]
        ];

    }

    public function getNotificationSettings(Request $request){
        $user=$request->user;
        $user=$user->only('push_likes','push_mentions', 'push_direct_messages', 'push_follows','push_watchlists', 'push_rooms','email_likes', 'email_mentions','email_direct_messages', 'email_follows', 'email_watchlist','email_rooms');
        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
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
            'display_message'=>'Settings have Been Updated',
            'data'=>[]
        ];

    }


    public function details(Request $request, $id=null){
        $user=$request->user;
        if($id)
            $profile=Customer::withCount(['posts', 'followers', 'followings'])->findOrFail($id);
        else
            $profile=Customer::withCount(['posts', 'followers', 'followings'])->findOrFail($user->id);
        if($profile->id!=$user->id){
            $profile->display_follow=1;
            $profile->display_message=1;
            $profile->options_type='other';
            if($user->followings()->where('customers.id', $profile->id)->first())
                $profile->is_followed=1;
            else
                $profile->is_followed=0;

            $posts=Post::with(['gallery', 'customer'=>function($customer){
                $customer->select('customers.id', 'name', 'username', 'image');
            }])->withCount(['replies', 'likes', 'shared'])
                ->where('posts.customer_id', $profile->id)
                ->orderBy('posts.created_at', 'desc');;

            $posts=$posts->paginate(env('PAGE_RESULT_COUNT'));

            Post::get_like_status($posts,$user);

        }else{
            $profile->display_follow=0;
            $profile->display_message=0;
            $profile->is_followed=0;
            $profile->options_type='self';

            $posts=Post::with(['gallery', 'customer'=>function($customer){
                $customer->select('customers.id', 'username', 'image');
            }])->withCount(['replies', 'likes', 'shared'])
                ->where('posts.customer_id', $profile->id)
                ->orderBy('posts.created_at', 'desc');;

            $posts=$posts->paginate(env('PAGE_RESULT_COUNT'));

            Post::get_like_status($posts,$user);
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('profile', 'posts')
        ];


    }

    public function block(Request $request, $profile_id){
        $user=$request->user;
        $profile=Customer::findOrFail($profile_id);

        $user->blocked()->syncWithoutDetaching($profile_id);

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Profile has been blocked',
            'data'=>[]
        ];
    }

    public function report(Request $request, $profile_id){
        $user=$request->user;
        $profile=Customer::findOrFail($profile_id);

        $request->validate([
            'reason'=>'required|max:500'
        ]);

        $user->reported()->syncWithoutDetaching([$profile_id=>['reason'=>$request->reason]]);

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Profile has been reported',
            'data'=>[]
        ];
    }

    public function message(Request $request, $id){

    }



}
