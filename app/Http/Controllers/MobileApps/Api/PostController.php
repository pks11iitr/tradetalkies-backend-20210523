<?php

namespace App\Http\Controllers\MobileApps\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PriceAlert;
use App\Models\Stock;
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
            'message'=>'',
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


        $feeds=Post::with(['gallery'=>function($gallery){
            $gallery->select('id', 'image');
        }, 'customer'=>function($customer){
            $customer->select('id', 'username', 'image');
        }])->withCount(['replies', 'likes', 'shared'])
            //self created posts
            ->where('customer_id', $user->id)
            //followings post
            ->orWhere(function($query) use($followings){
                $query->whereNull('room_id')
                    ->where('customer_id', $followings);
            })
            //room posts
            ->orWhere(function($query) use($rooms){
                $query->whereNotNull('room_id')
                    ->where('room_id', $rooms);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(env('PAGE_RESULT_COUNT'));

        Post::get_like_status($feeds,$user);

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'feeds')
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


        $feeds=Post::with(['gallery'=>function($gallery){
            $gallery->select('id', 'image');
        }, 'customer'=>function($customer){
            $customer->select('id', 'username', 'image');
        }])->withCount(['replies', 'likes', 'shared'])
            //self created posts
            ->where('customer_id', $user->id)
            //followings post
            ->orWhere(function($query) use($followings){
                $query->whereNull('room_id')
                    ->where('customer_id', $followings);
            })
            //room posts
            ->orWhere(function($query) use($rooms){
                $query->whereNotNull('room_id')
                    ->where('room_id', $rooms);
            })
            ->orderBy('views', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(env('PAGE_RESULT_COUNT'));

        Post::get_like_status($feeds,$user);

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'feeds')
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

        $feeds=Post::with(['gallery'=>function($gallery){
            $gallery->select('documents.id', 'image');
        }, 'customer'=>function($customer){
            $customer->select('customers.id', 'username', 'image');
        }])->withCount(['replies', 'likes', 'shared'])
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
            //self created posts
            ->orderBy('created_at', 'desc')
            ->paginate(env('PAGE_RESULT_COUNT'));

        Post::get_like_status($feeds,$user);

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'feeds')
        ];
    }

    public function suggested(Request $request){
        $feeds=[];
        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact( 'feeds')
        ];
    }


    public function postDetails(Request $request, $post_id){

    }


    public function store(Request $request){
        $request->validate([
            'content'=>'required|max:1000',
            'images'=>'required|array',
            'stock_ids'=>'array',
            'room_id'=>'integer'
        ]);

        $user=$request->user;

        $post=new Post($request->only('parent_id', 'content', 'room_id'));
        $user->posts()->save($post);

        if($request->stock_ids)
            $post->stocks()->sync($request->stock_ids);

        foreach($request->images as $image)
            $post->saveDocument($image, 'posts');

        return [
            'status'=>'success',
            'action'=>'success',
            'display_message'=>'Post Has Been Created Successfully',
            'data'=>[]
        ];

    }

    public function searchStocks(Request $request){

        $stocks=Stock::where(function($query)use($request){
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%');
        });

        $stocks = $stocks->take(5)->get();

        return [
            'status'=>'success',
            'action'=>'success',
            'message'=>'',
            'data'=>compact('stocks')
        ];
    }

//SQL: select count(*) as aggregate from `posts` where exists (select * from `posts` as `laravel_reserved_0` inner join `post_id` on `laravel_reserved_0`.`id` = `post_id`.`post_id` where `posts`.`id` = `post_id`.`stock_id` and `stocks`.`id` in (1, 2)) and (`customer_id` = 1 or (`room_id` is null and `customer_id` = 0) or (`room_id` is not null and `room_id` = 0)))",

}
