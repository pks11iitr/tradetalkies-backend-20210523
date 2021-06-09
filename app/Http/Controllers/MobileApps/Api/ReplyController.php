<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function store(Request $request){
        $request->validate([
           'parent_id'=>'required|integer',
           'content'=>'required|max:500',
        ]);

        $user=$request->user;

        $parent=Post::findOrFail($request->parent_id);
        if($parent->level==0)
            $level=1;
        else if($parent->level==1)
            $level=2;
        else
            $level=2;

        if(in_array($parent->level, [0,1]))
            $post=new Post(array_merge($request->only('parent_id', 'content', 'room_id'), ['level'=>$level]));
        else
            $post=new Post(array_merge($request->only( 'content', 'room_id'), ['level'=>$level, 'parent_id'=>$parent->parent_id]));
        $user->posts()->save($post);

        if($request->image){
            $post->saveDocument($request->image, 'posts');
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>[]
        ];

    }
}
