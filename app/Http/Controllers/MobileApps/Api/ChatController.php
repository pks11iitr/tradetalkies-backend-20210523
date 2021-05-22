<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewOrderNotification;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\Shoppr;
use App\Models\Store;
use App\Models\WorkLocations;
use App\Services\Notification\FCMNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{

    public function chathistory(Request $request){
        $user=$request->user;

        $users=Chat::with(['shoppr'=>function($user){
            $user->select('id', 'name', 'image');
        }])
            ->where('customer_id', $user->id)
            ->where('shoppr_id','!=', 0)
            ->orderBy('id', 'desc')
            ->get();

        $chatids=[];
        foreach($users as $chatid){
            $chatids[]=$chatid->id;
        }

        $messageids=[];
        if(!empty($chatids)){
            $messages=ChatMessage::whereIn('chat_id', $chatids)
                ->groupBy('chat_id')
                ->select(DB::raw('MAX(id) as message_id'))
                ->get();
            foreach($messages as $message){
                $messageids[]=$message->message_id;
            }
        }

        $messagelist=[];
        $messages=ChatMessage::whereIn('id', $messageids)
            ->get();
        foreach($messages as $message)
            $messagelist[$message->chat_id]=$message;


        $userchats=[];
        foreach($users as $userchat){
            $userchats[]=[
                'id'=>$userchat->id,
                'name'=>$userchat->shoppr->name,
                'image'=>$userchat->shoppr->image,
                'chat'=>$messagelist[$userchat->id]->message??('['.$messagelist[$userchat->id]->type.']'),
                'date'=>$messagelist[$userchat->id]->created_at,
            ];
        }

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('userchats')
        ];
    }

    public function startChat(Request $request, $store_id=null){

        $user=$request->user;
//        if($user->id==4){
//            $shoppr=Shoppr::find(4);
//        }else{
//            $shoppr=Shoppr::find(1);
//        }
        if(!$request->location)
            return [
                'status'=>'failed',
                'message'=>'Please provide location'
            ];

        $location=WorkLocations::extractlocationfromjson($request->location, $request->city);
        if(!$location)
            return [
                'status'=>'failed',
                'message'=>'Location is not servicable'
            ];

        $chat=Chat::create([
            'customer_id'=>$user->id,
            'shoppr_id'=>0,
            'lat'=>$request->lat,
            'lang'=>$request->lang,
            'location_id'=>$location->id
        ]);

        if($store_id){

            $store=Store::find($store_id);
            if($store){
                $store_message='Please get my items from this store. '.$store->store_name;
                ChatMessage::create([
                    'message'=>$store_message,
                    'direction'=>1,
                    'type'=>'store',
                    'chat_id'=>$chat->id,
                    'lat'=>$store->lat,
                    'lang'=>$store->lang
                ]);
            }
        }


        $message='I am excited to shop & deliver things to you!';

        ChatMessage::create([
            'message'=>$message,
            'direction'=>1,
            'type'=>'text',
            'chat_id'=>$chat->id
        ]);

        $share_location='Please share your delivery address and share the location on map.';

        ChatMessage::create([
            'message'=>$share_location,
            'direction'=>1,
            'type'=>'address-request',
            'chat_id'=>$chat->id
        ]);

        dispatch(new SendNewOrderNotification($chat->id, $location));

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>[
                'id'=>$chat->id
            ]
        ];
    }


    public function autoassign(Request $request, $chat_id){

        DB::beginTransaction();
            $chat=Chat::lockForUpdate()
                ->findOrFail($chat_id);

            if($chat->shoppr_id){
                DB::commit();
                return [
                    'status'=>'success',
                    'message'=>'Shoppr has been assigned'
                ];
            }


            if(!$request->location){
                DB::commit();
                return [
                    'status'=>'failed',
                    'message'=>'Please provide location'
                ];
            }


            $location=WorkLocations::extractlocationfromjson($request->location, $request->city);
            if(!$location){
                DB::commit();
                return [
                    'status'=>'failed',
                    'message'=>'Location is not servicable'
                ];
            }

            $shoppr=Shoppr::active()
                ->where('is_available', true)
                ->whereHas('locations', function($query)use($location) {
                $query->where('work_locations.id', $location->id);
            })->inRandomOrder()->first();

            if(!$shoppr){
                DB::commit();
                return [
                    'status'=>'failed',
                    'message'=>'Sorry! Currently no ShopR is available at this location'
                ];
            }

            $chat->shoppr_id=$shoppr->id;
            $chat->save();
        DB::commit();
        $shoppr->is_available=false;
        $shoppr->save();

//        Notification::create([
//            'user_id'=>$chat->customer->id,
//            'type'=>'individual',
//            'title'=>'Shoppr Assigned',
//            'description'=>'Shoppr Assigned',
//            'user_type'=>'CUSTOMER'
//        ]);
        $chat->customer->notify(new FCMNotification('ShopR Assigned', 'ShopR Has Been Assigned. Please upload shopping list', array_merge(['message'=>'ShopR Has Been Assigned. Please upload shopping list', 'title'=>'ShopR Assigned'], ['type'=>'chat-assigned', 'chat_id'=>''.$chat->id]),'chat_screen'));

        Notification::create([
            'user_id'=>$shoppr->id,
            'type'=>'individual',
            'title'=>'Order Assigned',
            'description'=>'Order Assigned',
            'user_type'=>'SHOPPR'
        ]);
        $shoppr->notify(new FCMNotification('Order Assigned', 'Order Assigned', array_merge(['message'=>'New Order', 'title'=>'Order Assigned'], ['type'=>'chat-assigned', 'chat_id'=>''.$chat->id]),'chat_screen'));

        return [
            'status'=>'success',
            'message'=>'Shoppr has been assigned'
        ];


    }


//    public function startChat(Request $request, $store_id=null){
//
//        $user=$request->user;
//        if($user->id==4){
//            $shoppr=Shoppr::find(4);
//        }else{
//            $shoppr=Shoppr::find(1);
//        }
//
//        $chat=Chat::create([
//            'customer_id'=>$user->id,
//            'shoppr_id'=>$shoppr->id
//        ]);
//
//        if($store_id){
//
//            $store=Store::find($store_id);
//            if($store){
//                $store_message='Please get my items from this store. '.$store->store_name;
//                ChatMessage::create([
//                    'message'=>$store_message,
//                    'direction'=>1,
//                    'type'=>'store',
//                    'chat_id'=>$chat->id,
//                    'lat'=>$store->lat,
//                    'lang'=>$store->lang
//                ]);
//            }
//        }
//
//
//        $message='I am excited to shop & deliver things to you!';
//
//        ChatMessage::create([
//            'message'=>$message,
//            'direction'=>1,
//            'type'=>'text',
//            'chat_id'=>$chat->id
//        ]);
//
//        $share_location='Please share your delivery address and share the location on map.';
//
//        ChatMessage::create([
//            'message'=>$share_location,
//            'direction'=>1,
//            'type'=>'address-request',
//            'chat_id'=>$chat->id
//        ]);
//
//        return [
//            'status'=>'success',
//            'message'=>'',
//            'data'=>[
//                'id'=>$chat->id
//            ]
//        ];
//    }


}
