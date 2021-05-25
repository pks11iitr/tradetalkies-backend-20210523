<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function feeds(Request $request){

        $user=$request->user;

        $watchlist=$user->watchlist()->select('id')->get()->map(function($element){
            return $element->id;
        })->toArray();

        $followings=$user->followings()->select('id')->get()->map(function($element){
            return $element->id;
        })->toArray();


        $feeds=Post::with('gallery')
            ->where(function($query) use($user, $watchlist,$followings){
                $query->whereIn('stock_id', $watchlist)
                    ->orWhereIn('customer_id', $followings)
                    ->orWhere('customer_id', $user->id);
            })
            ->paginate(10);

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'feeds')
        ];

    }

    public function store(Request $request){
        $request->validate([
            'content'=>'required|max:1000',
            'images'=>'required|array',
        ]);

        $user=$request->user;

        $post=new Post($request->only('parent_id', 'stock_id', 'content'));

        $user->posts()->save($post);

        foreach($request->images as $image)
            $post->saveDocument($image, 'posts');

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Post Has Been Created Successfully',
            'data'=>[]
        ];

    }


    public function myposts(){

    }
}
