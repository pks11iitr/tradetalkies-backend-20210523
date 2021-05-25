<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
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
           'fee'=>'required_if:type,Paid|integer|min:1'
        ]);
        if($request->type=='Paid')
            $room=new Room($request->only('name', 'fee','type'));
        else
            $room=new Room($request->only('name', 'type'));

        $request->user->rooms()->save($room);

        $room->saveImage($request->image, 'rooms');

        $room->members()->sync([$request->user->id]);

        $chats=[];

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Room Has Been Created',
            'data'=>compact( 'chats')
        ];

    }

    public function members(Request $request, $room_id){

        $user=$request->user;
        $room=Room::findOrFail($room_id);

        $members=$room->members()->paginate(env('PAGE_RESULT_COUNT'));

        $show_delete=0;
        if($room->created_by==$user->id)
        {
            $show_delete=1;
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Room Has Been Created',
            'data'=>compact( 'room', 'members', 'show_delete')
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



}
