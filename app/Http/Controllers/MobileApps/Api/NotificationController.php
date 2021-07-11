<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request){

        $user=auth()->guard('customerapi')->user();

        Notification::where('user_id', $user->id??0)
            ->update(['seen_at'=> date('Y-m-d H:i:s')]);

        if($user){
            $notifications=Notification::where(function($query) use($user){
                $query->where('user_id', $user->id);
            })
                ->orWhere('type','all');

        }else{
            $notifications=Notification::where('type','all');
        }

        $notifications=$notifications->select('description', 'type', 'type_id', 'var1', 'var2', 'var3', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate(50);

        return [
            'status'=>'success',
            'data'=>compact('notifications')
        ];

    }
}
