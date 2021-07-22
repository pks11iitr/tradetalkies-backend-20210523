<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Customer;
use App\Services\Notification\FCMNotification;
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
                    'chat'=>$chatlist[$userchat->id]->message??'[object]',
                    'date'=>Carbon::createFromFormat('Y-m-d H:i:s', $chatlist[$userchat->id]->getRawOriginal('created_at'))->diffForHumans(),
                    'username'=>$userchat->user2->username,
                ];
            }else{
                $userchats[]=[
                    'id'=>$userchat->user_1,
                    'name'=>$userchat->user1->name,
                    'image'=>$userchat->user1->image,
                    'chat'=>$chatlist[$userchat->id]->message??'[object]',
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

        $chatsobjrev=$chatsobj->reverse();

        $next_page_url=$chatsobj->nextPageUrl();
        $prev_page_url=$chatsobj->previousPageUrl();

        $chats=[];
        $type=null;
        foreach ($chatsobjrev as $c){
            $date=date('d M Y', strtotime($c->getRawOriginal('created_at')));
            if(!isset($chats[$date]))
                $chats[$date]=[
                    'date'=>$date,
                    'chats'=>[]
                ];

            if($c->user_1==$user->id ){
                $type='user1';
                $chats[$date]['chats'][]=[
                    'image'=>$c->user1->image,
                    'message'=>$c->message??'',
                    'chat_image'=>$c->image,
                    'date'=>$c->created_at,
                    'direction'=>$c->direction==0?'right':'left'
                ];
            }else{
                $type='user2';
                $chats[$date]['chats'][]=[
                    'image'=>$c->user2->image,
                    'message'=>$c->message??'',
                    'chat_image'=>$c->image,
                    'date'=>$c->created_at,
                    'direction'=>$c->direction==1?'right':'left'
                ];
            }
        }

        $chats=array_values($chats);

        if($type){
            $update=Chat::where(function($query) use($user, $user_id){
                $query->where('user_1', $user->id)
                    ->where('user_2', $user_id)
                    ->where('direction', 1);
            })
                ->orWhere(function($query) use($user, $user_id){
                    $query->where('user_1', $user_id)
                        ->where('user_2', $user->id)
                        ->where('direction', 0);
                });
            if($type=='user1')
                $update->update(['user1_seen_at'=> date('Y-m-d H:i:s')]);
            else
                $update->update(['user2_seen_at'=> date('Y-m-d H:i:s')]);

        }


        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('chats', 'next_page_url', 'prev_page_url')
        ];

    }


    public function chatPolling(Request $request, $user_id){

        $user=$request->user;

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
            ->where(function($query)use($user){
                $query->where(function($query) use($user){
                    $query->where('user_1', $user->id)
                        ->where('user1_seen_at', null);
                })
                    ->orWhere(function($query) use($user){
                        $query->where('user_2', $user->id)
                            ->where('user2_seen_at', null);
                    });
            })
            ->orderBy('id','desc')
            ->get();

        $chatsobjrev=$chatsobj->reverse();

        $type=null;
        $chats=[];
        foreach ($chatsobjrev as $c){
            $date=date('d M Y', strtotime($c->getRawOriginal('created_at')));
            if(!isset($chats[$date]))
                $chats[$date]=[
                    'date'=>$date,
                    'chats'=>[]
                ];

            if($c->user_1==$user->id ){
                $type='user1';
                $chats[$date]['chats'][]=[
                    'image'=>$c->user1->image,
                    'message'=>$c->message??'',
                    'chat_image'=>$c->image,
                    'date'=>$c->created_at,
                    'direction'=>$c->direction==0?'right':'left'
                ];
            }else{
                $type='user2';
                $chats[$date]['chats'][]=[
                    'image'=>$c->user2->image,
                    'message'=>$c->message??'',
                    'chat_image'=>$c->image,
                    'date'=>$c->created_at,
                    'direction'=>$c->direction==1?'right':'left'
                ];
            }
        }

        if($type){
            $update=Chat::where(function($query) use($user, $user_id){
                $query->where('user_1', $user->id)
                    ->where('user_2', $user_id)
                    ->where('direction', 1);
            })
                ->orWhere(function($query) use($user, $user_id){
                    $query->where('user_1', $user_id)
                        ->where('user_2', $user->id)
                        ->where('direction', 0);
                });
            if($type=='user1')
                $update->update(['user1_seen_at'=> date('Y-m-d H:i:s')]);
            else
                $update->update(['user2_seen_at'=> date('Y-m-d H:i:s')]);

        }

        $chats=array_values($chats);

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>compact('chats')
        ];
    }

    public function send(Request $request, $user_id)
    {
        $request->validate([
            'message' => 'required'
        ]);

        $user = $request->user;
        $receiver = Customer::findOrFail($user_id);

        $chat = Chat::where(function ($query) use ($user, $receiver) {
            $query->where('user_1', $user->id)
                ->where('user_2', $receiver->id);
        })->orWhere(function ($query) use ($user, $receiver) {
            $query->where('user_1', $receiver->id)
                ->where('user_2', $user->id);
        })->first();

        if (!$chat)
            $is_first_approved = 0;
        else if ($chat->user_1 == $user->id && $chat->direction == 0 && $chat->is_first_approved == 0) {
            return [
                'status'=>'failed',
                'message'=>'You can send message only when receiver replies your last message'
            ];
        }
        else if ($chat->user_2 == $user->id && $chat->direction == 1 && $chat->is_first_approved == 0){
            return [
                'status'=>'failed',
                'message'=>'You can send message only when receiver replies your last message'
            ];
    }
        else{
            $is_first_approved = 1;
    }

        if($is_first_approved && $chat->is_first_approved==0){
            $chat->is_first_approved=1;
            $chat->save();
        }


        if($user->id<$user_id){

            $chat=Chat::create([
                'user_1'=>$user->id,
                'user_2'=>$user_id,
                'message'=>$request->message,
                'direction'=>0,
                'is_first_approved'=>$is_first_approved,
                'user1_seen_at'=>date('Y-m-d H:i:s')
            ]);

        }else{
            $chat=Chat::create([
                'user_1'=>$user_id,
                'user_2'=>$user->id,
                'message'=>$request->message,
                'direction'=>1,
                'is_first_approved'=>$is_first_approved,
                'user2_seen_at'=>date('Y-m-d H:i:s')
            ]);
        }

        if($request->image)
            $chat->saveImage($request->image, 'chats');

//        $receiver->notify( new FCMNotification('New message from '.$user->name, $request->message, ['message'=>$request->message, 'type'=>'chat', 'chat_id'=>''.$chat->chat_id], 'chat_screen'));

        return [
            'status'=>'success',
            'message'=>'',
            'data'=>[]
        ];

    }
}
