<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PriceAlert;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function feeds(Request $request){

        switch($request->type){
            case 'all': return $this->all($request);
            case 'trending': return $this->trending($request);
            case 'watchlist': return $this->watchlist($request);
            case 'suggested': return $this->suggested($request);
        }

        return [
            'status'=>'failed',
            'action'=>'invalid_type',
            'display_message'=>'',
            'data'=>[]
        ];

    }

    public function all(Request $request){
        $user=$request->user;

//        $watchlist=$user->watchlist()->select('id')->get()->map(function($element){
//            return $element->id;
//        })->toArray();

        $followings=$user->followings()->select('customers.id')->get()->map(function($element){
            return $element->id;
        })->toArray();

        $rooms=$user->rooms()->select('rooms.id')->get()->map(function($element){
            return $element->id;
        })->toArray();


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
            ->where(function($query) use($user,$followings,$rooms){
                $query->where('customer_id', $user->id)
                    //followings post
                    ->orWhere(function($query) use($followings){
                        $query->whereNull('room_id')
                            ->whereIn('customer_id', $followings);
                    })
                    //room posts
                    ->orWhere(function($query) use($rooms){
                        $query->whereNotNull('room_id')
                            ->whereIn('room_id', $rooms);
                    });
            })
            ->whereHas('customer', function($customer) use($user){
                $customer->whereDoesntHave('blockedBy', function($blockedby) use($user){
                    $blockedby->where('block_profile.user_id', $user->id);
                });
            })
            ->orderBy('id', 'desc');

        $feeds=Post::applyDateSearchFilter($feeds,$request->date_type, $request->date_start,$request->date_end, $request->search_term);

        $feeds=$feeds->paginate(1);

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
            'data'=>compact( 'feeds', 'mentions')
        ];
    }

    public function trending(Request $request){
        $user=$request->user;

//        $watchlist=$user->watchlist()->select('id')->get()->map(function($element){
//            return $element->id;
//        })->toArray();

        $followings=$user->followings()->select('customers.id')->get()->map(function($element){
            return $element->id;
        })->toArray();

        $rooms=$user->rooms()->select('rooms.id')->get()->map(function($element){
            return $element->id;
        })->toArray();


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
            ->where(function($query) use($user,$followings,$rooms){
                $query->where('customer_id', $user->id)
                    //followings post
                    ->orWhere(function($query) use($followings){
                        $query->whereNull('room_id')
                            ->whereIn('customer_id', $followings);
                    })
                    //room posts
                    ->orWhere(function($query) use($rooms){
                        $query->whereNotNull('room_id')
                            ->whereIn('room_id', $rooms);
                    });
            })
            ->whereHas('customer', function($customer) use($user){
                $customer->whereDoesntHave('blockedBy', function($blockedby) use($user){
                    $blockedby->where('block_profile.user_id', $user->id);
                });
            })
            ->orderBy('views', 'desc')
            ->orderBy('id', 'desc');

        $feeds=Post::applyDateSearchFilter($feeds,$request->date_type, $request->date_start,$request->date_end, $request->search_term);

        $feeds=$feeds->paginate(env('PAGE_RESULT_COUNT'));

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

        $mentions=Post::getMentionsList($feeds);

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact( 'feeds', 'mentions')
        ];
    }

    public function watchlist(Request $request){
        $user=$request->user;

        $watchlist=$user->watchlist()->select('stocks.id')->get()->map(function($element){
            return $element->id;
        })->toArray();
        //return $watchlist;

        $followings=$user->followings()->select('customers.id')->get()->map(function($element){
            return $element->id;
        })->toArray();

        $rooms=$user->rooms()->select('rooms.id')->get()->map(function($element){
            return $element->id;
        })->toArray();


        //die;

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
        ])
            ->withCount(['replies', 'likes', 'shared'])
            ->where('parent_id', null)
            ->whereHas('stocks', function($stocks) use($watchlist){
                $stocks->whereIn('stocks.id', $watchlist);
            })
            ->where(function($query) use($user, $rooms, $followings){
                $query->where('customer_id', $user->id)
                    //followings post
                    ->orWhere(function($query) use($followings){
                        $query->whereNull('room_id')
                            ->where('customer_id', $followings);
                    })
                    //room posts
                    ->orWhere(function($query) use($rooms){
                        $query->whereNotNull('room_id')
                            ->where('room_id', $rooms);
                    });
            })
            ->whereHas('customer', function($customer) use($user){
                $customer->whereDoesntHave('blockedBy', function($blockedby) use($user){
                    $blockedby->where('block_profile.user_id', $user->id);
                });
            })
            //self created posts
            ->orderBy('id', 'desc');

        $feeds=Post::applyDateSearchFilter($feeds,$request->date_type, $request->date_start,$request->date_end, $request->search_term);

        $feeds=$feeds->paginate(env('PAGE_RESULT_COUNT'));

        Post::get_like_status($feeds,$user);

        $mentions=Post::getMentionsList($feeds);
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
            'data'=>compact( 'feeds', 'mentions')
        ];
    }

    public function suggested(Request $request){
        $feeds=[];
        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact( 'feeds')
        ];
    }


    public function postDetails(Request $request, $post_id){

        $user=$request->user;

        $post=Post::with(['gallery', 'mentions'=>function($mention){
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
            ->find($post_id);

        $post_ids=[$post->id];

        $replies=Post::with(['gallery', 'customer'=>function($customer){
            $customer->select('customers.id', 'username', 'image');
        }, 'mentions'=>function($customer){
            $customer->select('customers.id', 'name', 'username', 'image');
        }])
            ->withCount(['replies', 'likes'])
            ->where('parent_id', $post->id)
            ->orderBy('id', 'desc')
            ->paginate(env('PAGE_RESULT_COUNT'));

        $p_rep_ids=[];
        foreach($replies as $rep)
            $p_rep_ids[]=$rep->id;

        //var_dump($p_rep_ids);die;

        if(count($p_rep_ids))
        {
            $c_replies=Post::with(['gallery', 'customer'=>function($customer){
                $customer->select('customers.id', 'username', 'image');
            }, 'mentions'=>function($customer){
                $customer->select('customers.id', 'name', 'username', 'image');
            }])
            ->withCount(['replies', 'likes'])
            ->whereIn('parent_id', $p_rep_ids)->get();
            $c_rep_arr=[];
            foreach($c_replies as $r){
                if(!isset($c_rep_arr[$r->parent_id]))
                    $c_rep_arr[$r->parent_id]=[];
                $c_rep_arr[$r->parent_id][]=$r;
            }
        }
        //return $c_rep_arr;
        foreach($replies as $r){
            if(isset($c_rep_arr[$r->id])){
                $r->replies=[
                    'data'=>$c_rep_arr[$r->id]
                ];
            }else{
                $r->replies=[
                    'data'=>[]
                ];
            }

        }
        if(!empty($c_replies))
            $mentions=Post::getMentionsList($replies->merge(new Collection([$post]))->merge($c_replies));
        else
            $mentions=Post::getMentionsList($replies->merge(new \Illuminate\Support\Collection([$post])));

        //$post->mentions=null;

        foreach($replies as $reply){
            $post_ids[]=$reply->id;
            foreach($reply->replies['data'] as $rreply){
                $post_ids[]=$rreply->id;
            }
            //$reply->mentions=null;
        }

        $user_likes=$user->likes()
            ->whereIn('posts.id', $post_ids)
            ->get()->map(function($element){
               return $element->id;
            })->toArray();

        $reported=$user->reported->map(function($element){
            return $element->id;
        })->toArray();

        $post->is_liked=in_array($post->id, $user_likes)?1:0;
        $post->is_reported=in_array($post->id, $reported)?1:0;
        $post->options_type=($post->customer_id==$user->id)?'self':'other';


        foreach($replies as $reply){
            $reply->is_liked = in_array($reply->id, $user_likes)?1:0;
            $reply->options_type=($user->id!=$reply->customer_id)?'other':'self';
            $reply->is_reported=0;
            if(in_array($reply->id, $reported))
                $reply->is_reported=1;
            else
                $reply->is_reported=0;
            foreach($reply->replies['data'] as $rreply){
                $rreply->is_liked = in_array($rreply->id, $user_likes)?1:0;
                $rreply->options_type=($user->id!=$rreply->customer_id)?'other':'self';
                if(in_array($rreply->id, $reported))
                    $rreply->is_reported=1;
                else
                    $rreply->is_reported=0;
            }
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>compact('post','replies', 'mentions')
        ];



    }


    public function store(Request $request){
        $request->validate([
            'content'=>'required|max:1000',
            'images'=>'nullable|array',
            'stock_ids'=>'nullable|array',
            'room_id'=>'nullable|integer',
            'mentions'=>'array',
            'shared_post_id'=>'nullable|integer'
        ]);

        $user=$request->user;

        $post=new Post($request->only('parent_id', 'content', 'room_id', 'shared_post_id'));
        $user->posts()->save($post);

        if($request->stock_ids)
            $post->stocks()->sync($request->stock_ids);

        if($request->images){
            foreach($request->images as $image)
                $post->saveDocument($image, 'posts');
        }

        if(!empty($request->mentions))
            $post->mentions()->sync($request->mentions);

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Post Has Been Created Successfully',
            'data'=>[]
        ];

    }


    public function likePost(Request $request, $post_id){
        $user=$request->user;

        $post=Post::with(['likes'=>function($likes) use($user){
                $likes->where('customers.id', $user->id);
            }])->findOrFail($post_id);

        if(!count($post->likes)){
            $post->likes()->syncWithoutDetaching($user->id);
        }else{
            $post->likes()->detach($user->id);
        }

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'',
            'data'=>[]
        ];

    }

//SQL: select count(*) as aggregate from `posts` where exists (select * from `posts` as `laravel_reserved_0` inner join `post_id` on `laravel_reserved_0`.`id` = `post_id`.`post_id` where `posts`.`id` = `post_id`.`stock_id` and `stocks`.`id` in (1, 2)) and (`customer_id` = 1 or (`room_id` is null and `customer_id` = 0) or (`room_id` is not null and `room_id` = 0)))",

}
