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
                    'date'=>Carbon::createFromFormat('Y-m-d H:i:s', $chatlist[$userchat->id]->getRawOriginal('created_at'))->diffForHumans(),
                    'username'=>$userchat->user2->username,
                ];
            }else{
                $userchats[]=[
                    'id'=>$userchat->user_1,
                    'name'=>$userchat->user1->name,
                    'image'=>$userchat->user1->image,
                    'chat'=>$chatlist[$userchat->id]->message,
                    'date'=>Carbon::createFromFormat('Y-m-d H:i:s', $chatlist[$userchat->id]->getRawOriginal('created_at'))->diffForHumans(),
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


    public function chatDetails(Request $request, $user_id){

        $user=$request->user;

        Chat::where(function($query) use($user, $user_id){
                $query->where('user_1', $user->id)
                    ->where('user_2', $user_id)
                    ->where('direction', 1);
            })
            ->orWhere(function($query) use($user, $user_id){
                $query->where('user_1', $user_id)
                    ->where('user_2', $user->id)
                    ->where('direction', 0);
            })->update('seen_at', date('Y-m-d H:i:s'));

        $chatsobj=Chat::with(['user1', 'user2'])
            ->where(function($query) use($user, $user_id){
                $query->where(function($query) use($user, $user_id){
                    $query->where('user_1', $user->id)
                        ->where('user_2', $user_id);
                })
                    ->orWhere(function($query) use($user, $user_id){
                        $query->where('user_1', $user_id)
                            ->where('user_2', $user->id);
                    });
            })
            ->orderBy('id','desc')
            ->paginate(20);

        $next_page_url=$chatsobj->nextPageUrl();
        $prev_page_url=$chatsobj->previousPageUrl();

        $chats=[];
        foreach ($chatsobj as $c){
            if($c->user_1==$user->id){
                $chats[]=[
                    'image'=>$c->user1->image,
                    'message'=>$c->message,
                    'date'=>$c->created_at,
                    'direction'=>$c->direction
                ];
            }else{
                $chats[]=[
                    'image'=>$c->user2->image,
                    'message'=>$c->message,
                    'date'=>$c->created_at,
                    'direction'=>$c->direction
                ];
            }
        }

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('chats', 'next_page_url', 'prev_page_url')
        ];

    }


    public function chatPolling(Request $request){

    }
}
