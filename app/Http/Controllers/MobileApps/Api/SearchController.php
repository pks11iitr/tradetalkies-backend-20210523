<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Stock;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchStocks(Request $request){

        $stocks=Stock::where(function($query)use($request){
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%');
        });

        $stocks = $stocks->take(5)->get();

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('stocks')
        ];
    }

    public function mentionsList(Request $request){
        $user=$request->user;
        $search=$request->search??'';
        $profiles=Customer::where(function($query) use($search){
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('username', 'like', '%'.$search.'%');
        })
            ->select('id', 'name', 'username', 'image')
            ->where('id','!=', $user->id)
            ->orderBy('id', 'desc')
            ->skip(0)->take(500)
            ->get();

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('profiles')
        ];
    }
}
