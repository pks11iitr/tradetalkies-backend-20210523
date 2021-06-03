<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function details(Request $request, $stock_id){

        $user=$request->user;

        $stock=Stock::findOrFail($stock_id);

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
            ->whereHas('stocks', function($stocks) use($stock){
                $stocks->where('stocks.id', $stock->id);
            })
            ->where(function($query) use($user, $rooms, $followings){
                $query->where('customer_id', $user->id)
                    //followings post
                    ->orWhere(function($query) use($followings){
                        $query->whereNull('room_id')
                            ->where('customer_id', $followings);
                    })
                    //room posts
                    ->orWhere(function($query) use($rooms){
                        $query->whereNotNull('room_id')
                            ->where('room_id', $rooms);
                    });
            })
            //self created posts
            ->orderBy('created_at', 'desc')
            ->paginate(env('PAGE_RESULT_COUNT'));

        $webview=route('stock.webview', ['stock_id'=>$stock->id]);

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'feeds', 'stock', 'webview')
        ];

    }
}
