<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Post;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request){

        switch($request->type){
            case 'free': $rooms=Room::userFreeRooms($request); break;
            case 'paid': $rooms=Room::userPaidRooms($request); break;
            case 'myrooms': $rooms=Room::userParticipatedRooms($request); break;
            default: $rooms=Room::userParticipatedRooms($request);
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact( 'rooms')
        ];

    }


    public function add(Request $request){
        $request->validate([
           'name'=>'required|max:150',
           'type'=>'required|in:Free,Paid',
           'image'=>'required',
           'fee'=>'required_if:type,Paid|integer|min:0'
        ]);
        if($request->type=='Paid')
            $room=new Room($request->only('name', 'fee','type'));
        else
            $room=new Room($request->only('name', 'type'));

        $request->user->myrooms()->save($room);

        $room->saveImage($request->image, 'rooms');

        $room->members()->sync([$request->user->id]);

        $posts=[];

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Room Has Been Created',
            'data'=>compact('room')
        ];

    }

    public function members(Request $request, $room_id){

        $user=$request->user;
        $room=Room::withCount('members')->findOrFail($room_id);

        $members=Customer::whereHas('rooms', function($rooms) use($room){
            $rooms->where('rooms.id', $room->id);
        })->select('customers.id', 'username', 'customers.image', 'name')
            ->paginate(env('PAGE_RESULT_COUNT'));

        $show_delete=0;
        $show_leave=1;
        if($room->created_by==$user->id)
        {
            $show_delete=1;
            $show_leave=0;
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact( 'room', 'members', 'show_delete', 'show_leave')
        ];

    }


    public function delete(Request $request, $room_id){

        $room=Room::where('created_by', $request->user->id)
            ->findOrFail($room_id);

        $room->members()->detach();

        $room->delete();

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Room Has Been Deleted',
            'data'=>[]
        ];

    }

    public function roomPosts(Request $request, $room_id){
        $user=$request->user;

        $room=Room::withCount('members')->findOrFail($room_id);

        if($room->created_by==$user->id)
            $room->create_post=1;
        else
            $room->create_post=0;

        $feeds=Post::with(['gallery', 'mentions'=>function($mention){
            $mention->select('post_mentions.customer_id as id', 'username', 'name', 'image');
        }, 'customer'=>function($customer){
            $customer->select('id', 'username', 'name', 'image');
        },'sharedPost'=>function($shared){
            $shared->with(['gallery', 'mentions'=>function($mention){
                $mention->select('post_mentions.customer_id as id', 'username', 'name', 'image');
            }, 'customer'=>function($customer){
                $customer->select('id', 'username', 'name', 'image');
            }]);
        }
        ])->withCount(['replies', 'likes', 'shared'])
            //self created posts
            ->where('parent_id', null)
//            ->where(function($query) use($user,$followings,$rooms){
//                $query->where('customer_id', $user->id)
//                    //followings post
//                    ->orWhere(function($query) use($followings){
//                        $query->whereNull('room_id')
//                            ->whereIn('customer_id', $followings);
//                    })
//                    //room posts
//                    ->orWhere(function($query) use($rooms){
//                        $query->whereNotNull('room_id')
//                            ->whereIn('room_id', $rooms);
//                    });
//            })

            ->whereHas('customer', function($customer) use($user){
                $customer->whereDoesntHave('blockedBy', function($blockedby) use($user){
                    $blockedby->where('block_profile.user_id', $user->id);
                });
            })
            ->where('room_id', $room_id)
            ->orderBy('id', 'desc');

        $feeds=$feeds->paginate(env('PAGE_RESULT_COUNT'));

        $mentions=Post::getMentionsList($feeds);

        Post::get_like_status($feeds,$user);
        Post::getReportStatus($feeds,$user);

//        $mentions=[
//            [
//                'id'=>'@#1#',
//                'name'=>'Pankaj Sengar'
//            ],
//            [
//                'id'=>'@#2#',
//                'name'=>'Bharat Arora'
//            ],
//            [
//                'id'=>'@#3#',
//                'name'=>'Random'
//            ],
//        ];

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact( 'feeds', 'mentions', 'room')
        ];
    }


    public function joinRoom(Request $request, $room_id){
        $user=$request->user;
        $room=Room::findOrFail($room_id);

        if($room->type=='Free') {
            $room->members()->syncWithoutDetaching($user->id);
            $action = 'joined';
            $data=[];
        }
        else{
            $action='payment';
            $data=[];
        }

        return [
            'status'=>'success',
            'action'=>$action,
            'display_message'=>'',
            'data'=>$data
        ];
    }

    public function leaveRoom(Request $request, $room_id){
        $user=$request->user;
        $room=Room::findOrFail($room_id);

        $room->members()->detach($user->id);

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>[]
        ];

    }

//Select content, room_id from (
//SELECT *,
//@order_rank := IF(@current_room = room_id, @order_rank + 1, 1) AS order_rank,
//@current_room := room_id
//FROM posts, (SELECT @order_rank := 0, @current_room :=0) r where room_id is not null and  parent_id is null ORDER BY room_id desc,id DESC ) as temp where order_rank=1


}
