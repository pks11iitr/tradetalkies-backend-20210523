<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationLog;
use App\Models\Notification;
use App\Models\Shoppr;
use App\Models\Stock;
use App\Models\WorkLocations;
use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    public function index(Request $request){

       $user=$request->user;

       $trending=Stock::get();

       $watchlist=$user->watchlist;

       return [
           'status'=>'success',
           'action'=>'success',
           'message'=>'',
           'data'=>compact('trending', 'watchlist')
       ];


    }
}
