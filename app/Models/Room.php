<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Room extends Model
{
    use HasFactory,DocumentUploadTrait;

    protected $table='rooms';

    protected $fillable=['created_by', 'name','image', 'type', 'fee'];


    public function creator(){
        return $this->belongsTo('App\Models\Customer', 'created_by');
    }


    public function members(){
        return $this->belongsToMany('App\Models\Customer', 'room_members', 'room_id', 'customer_id');
    }

    public function getImageAttribute($value){
        if($value)
            return Storage::url($value);
        return Storage::url('customers/default.jpeg');
    }

    public static function userFreeRooms(Request $request){
        $user=$request->user;

        $rooms=$user->rooms()->select('rooms.id')->get()->map(function($element){
            return $element->id;
        })->toArray();

        $rooms=Room::withCount('members')
            ->whereNotIn('id', $rooms)
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

        $rooms=Room::withCount('members')
            ->whereNotIn('id', $rooms)
            ->where('type', 'Paid')
            ->paginate(env('PAGE_RESULT_COUNT'));

        return $rooms;

    }


    public static function userParticipatedRooms(Request $request){

        $user=$request->user;
        $rooms=Room::whereHas('members', function($member)use($user){
           $member->where('customers.id', $user->id);
        })
            ->withCount('members')
            ->paginate(env('PAGE_RESULT_COUNT'));

        foreach($rooms as $r){
            $r->display_time=Carbon::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s', strtotime($r->created_at)))->diffForHumans();
            $r->small_text='This is random demo text';
        }

        return $rooms;

    }




}
