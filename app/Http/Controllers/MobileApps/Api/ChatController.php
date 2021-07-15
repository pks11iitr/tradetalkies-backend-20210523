<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function chatlist(Request $request){

        $user=$request->user;

        $users=Chat::with(['user1'=>function($user){
            $user->select('id', 'name', 'image', 'username');
        }, 'user2'=>function($user){
            $user->select('id', 'name', 'image', 'username');
        }])
            ->where('user_1', '!=', DB::raw('user_2'))
            ->whereHas('user1', function($user1){
                $user1->where('customers.isactive', true);
            })
            ->whereHas('user2', function($user2){
                $user2->where('customers.isactive', true);
            })
            //only approved or received chats will be visible
            ->where(function($query) use($user){
                $query->where('is_first_approved', true)
                    ->orWhere(function($query) use($user){
                        $query->where(function($query) use($user){
                            $query->where('user_1', $user->id)
                                ->where('direction', 1);
                        })
                            ->orWhere(function($query) use($user){
                                $query->where('user_2', $user->id)
                                    ->where('direction', 0);
                            });
                    });
            })
            ->select(DB::raw('max(id) as id'), 'chats.user_1', 'chats.user_2')
            ->groupBy('chats.user_1', 'chats.user_2')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $chatids=[];
        foreach($users as $chatid){
            $chatids[]=$chatid->id;
        }

        $chatlist=[];
        if(!empty($chatids)){
            $chats=Chat::whereIn('id', $chatids)
                ->get();
            foreach($chats as $chat){
                $chatlist[$chat->id]=$chat;
            }

        }


        $userchats=[];
        foreach($users as $userchat){
            if($chatlist[$userchat->id]->user_1==$user->id){
                $userchats[]=[
                    'id'=>$userchat->user_2,
                    'name'=>$userchat->user2->name,
                    'image'=>$userchat->user2->image,
                    'chat'=>$chatlist[$userchat->id]->message,
                    'date'=>Carbon::createFromFormat($chatlist[$userchat->id]->getRawOriginal('created_at'))->diffForHumans(),
                    'username'=>$userchat->user2->username,
                ];
            }else{
                $userchats[]=[
                    'id'=>$userchat->user_1,
                    'name'=>$userchat->user1->name,
                    'image'=>$userchat->user1->image,
                    'chat'=>$chatlist[$userchat->id]->message,
                    'date'=>Carbon::createFromFormat($chatlist[$userchat->id]->getRawOriginal('created_at'))->diffForHumans(),
                    'username'=>$userchat->user1->username,
                ];
            }
        }

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('userchats', 'users')
        ];


    }
}
