<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceAlert;
use App\Models\Stock;
use Illuminate\Http\Request;

class PriceAlertController extends Controller
{
    public function myalerts(Request $request){
        $user=$request->user;

        $pricealerts=PriceAlert::with('stock')
            ->where('user_id', $user->id)
            ->get();

        $alerts=[];
        foreach($pricealerts as $alert)
            $alerts[]=[
                'id'=>$alert->id,
                'name'=>$alert->stock->name,
                'code'=>$alert->stock->code,
                'current_price'=>$alert->stock->price,
                'target_price'=>$alert->alert_price,
            ];

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('alerts')
        ];
    }

    public function add(Request $request){
        $user=$request->user;

        $request->validate([
            'stock_id'=>'required|integer',
            'alert_price'=>'required|numeric'
        ]);

        $stock=Stock::findOrFail($request->stock_id);

        $pricealerts=PriceAlert::where('stock_id', $request->stock_id)
            ->where('user_id', $user->id)
            ->first();

        if($pricealerts)
            return [
                'status'=>'failed',
                'action'=>'already_added',
                'display_message'=>'Stock is already in alerts',
                'data'=>[]
            ];

        PriceAlert::create(array_merge($request->only('stock_id', 'alert_price'), ['user_id'=>$user->id]));

        return [
            'status'=>'success',
            'action'=>'added',
            'display_message'=>'Stock has been added to alerts',
            'data'=>[]
        ];


    }

    public function delete(Request $request, $id)
    {
        $user = $request->user;

        $alert=PriceAlert::where('user_id', $user->id)
            ->findOrFail($id);

        $alert->delete();

        return [
            'status'=>'success',
            'action'=>'deleted',
            'display_message'=>'Stock has been deleted from alerts',
            'data'=>[]
        ];

    }


    public function search(Request $request){
        $user=$request->user;

        $alerts=PriceAlert::where('user_id', $user->id)
            ->select('stock_id')
            ->get()->map(function($element){
                return $element->stock_id;
            })->toArray();

        $stocks=Stock::where(function($query)use($request){
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%');
        });
        //return $alerts;
        if($alerts)
            $stocks = $stocks->whereNotIn('id', $alerts);
        $stocks = $stocks->take(5)->get();

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('stocks')
        ];

    }


}
