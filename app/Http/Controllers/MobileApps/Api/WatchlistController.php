<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function index(Request $request){
        $user=$request->user;
        $watchlist=$user->watchlist;

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'watchlist')
        ];

    }

    public function addToWatchList(Request $request, $stock_id){
        $user=$request->user;

        $stock=Stock::findOrFail($stock_id);

        $user->watchlist()->syncWithoutDetaching([$stock->id]);

        $watchlist=$user->watchlist;

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact( 'watchlist')
        ];

    }


    public function removeFromWatchList(Request $request, $stock_id){
        $user=$request->user;

        $stock=Stock::findOrFail($stock_id);

        $user->watchlist()->detach([$stock->id]);

        $watchlist=$user->watchlist;

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact( 'watchlist')
        ];

    }

}
