<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PriceAlert;
use App\Models\Stock;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function feeds(Request $request){

        $user=$request->user;

//        $watchlist=$user->watchlist()->select('id')->get()->map(function($element){
//            return $element->id;
//        })->toArray();

        $followings=$user->followings()->select('customers.id')->get()->map(function($element){
            return $element->id;
        })->toArray();

        $rooms=$user->rooms()->select('rooms.id')->get()->map(function($element){
            return $element->id;
        })->toArray();


        $feeds=Post::with(['gallery'=>function($gallery){
            $gallery->select('id', 'image');
        }, 'customer'=>function($customer){
            $customer->select('id', 'username', 'image');
        }])
            //self created posts
            ->where('customer_id', $user->id)
            //followings post
            ->orWhere(function($query) use($followings){
                $query->whereNull('room_id')
                    ->where('customer_id', $followings);
            })
            //room posts
            ->orWhere(function($query) use($rooms){
                $query->whereNotNull('room_id')
                    ->where('room_id', $rooms);
            })
            ->orderBy('created_at', 'desc')
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
            'stock_ids'=>'array',
            'room_id'=>'integer'
        ]);

        $user=$request->user;

        $post=new Post($request->only('parent_id', 'content', 'room_id'));

        if(!$request->stock_ids)
            $post->stocks()->sync($request->stock_ids);

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

    public function searchStocks(Request $request){

        $stocks=Stock::where(function($query)use($request){
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%');
        });

        $stocks = $stocks->take(5)->get();

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact('stocks')
        ];
    }


    public function myposts(){

    }
}
