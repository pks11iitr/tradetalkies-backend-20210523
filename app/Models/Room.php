<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory,DocumentUploadTrait;

    protected $table='rooms';

    protected $fillable=['created_by', 'name','image', 'type', 'fee'];


    public function creator(){
        return $this->belongsTo('App\Models\Customer', 'created_by');
    }


    public function members(){
        return $this->hasMany('App\Models\Customer', 'room_members', 'room_id', 'customer_id');
    }

    public static function userFreeRooms(Request $request){
        $user=$request->user;

        $rooms=$user->rooms()->select('rooms.id')->get()->map(function($element){
            return $element->id;
        })->toArray();

        $rooms=Room::whereNotIn('id', $rooms)
            ->where('type', 'Free')
            ->paginate(env('PAGE_RESULT_COUNT'));

        return $rooms;

    }

    public static function userPaidRooms(Request $request){
        $user=$request->user;

        $rooms=$user->rooms()->select('rooms.id')
            ->get()
            ->map(function($element){
            return $element->id;
        })->toArray();

        $rooms=Room::whereNotIn('id', $rooms)
            ->where('type', 'Paid')
            ->paginate(env('PAGE_RESULT_COUNT'));

        return $rooms;

    }


    public static function userParticipatedRooms(Request $request){

        $user=$request->user;
        return $user->rooms()->paginate(env('PAGE_RESULT_COUNT'));

    }




}
