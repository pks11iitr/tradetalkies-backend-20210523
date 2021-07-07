<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReplyController extends Controller
{
    public function store(Request $request){
        $request->validate([
           'parent_id'=>'required|integer',
           'content'=>'required|max:500',
            'mentions'=>'array'
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

        if(!empty($request->mentions))
            $post->mentions()->sync($request->mentions);

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>[]
        ];

    }


    public function replyDetails(Request $request, $post_id){
        $user=$request->user;

        $post=Post::with(['gallery', 'customer'=>function($customer){
            $customer->select('customers.id', 'username', 'image');
        }, 'mentions'=>function($customer){
            $customer->select('customers.id', 'name', 'username', 'image');
        }])->withCount(['replies', 'likes', 'shared'])
            ->find($post_id);

        $post_ids=[$post->id];

        $replies=Post::with(['gallery', 'customer'=>function($customer){
            $customer->select('customers.id', 'username', 'image');
        },'mentions'=>function($customer){
            $customer->select('customers.id', 'name', 'username', 'image');
        }])
            ->withCount(['replies', 'likes'])
            ->where('parent_id', $post->id)
            ->orderBy('id', 'desc')
            ->paginate(env('PAGE_RESULT_COUNT'));

        $mentions=Post::getMentionsList($replies->merge(new Collection([$post])));

        foreach($replies as $reply){
            $post_ids[]=$reply->id;
        }

        $user_likes=$user->likes()
            ->whereIn('posts.id', $post_ids)
            ->get()->map(function($element){
                return $element->id;
            })->toArray();

        $post->is_liked=in_array($post->id, $user_likes)?1:0;

        foreach($replies as $reply){
            $reply->is_liked = in_array($reply->id, $user_likes)?1:0;
            foreach($reply->replies as $rreply){
                $rreply->is_liked = in_array($rreply->id, $user_likes)?1:0;
            }
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('post','replies', 'mentions')
        ];
    }
}
