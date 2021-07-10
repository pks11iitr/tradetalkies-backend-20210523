<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory, DocumentUploadTrait;

    protected $table='posts';

    protected $fillable=['parent_id', 'customer_id', 'content', 'room_id', 'views', 'level'];

    public function stocks(){
        return $this->belongsToMany('App\Models\Stock', 'stock_posts', 'post_id', 'stock_id');
    }

    public function room(){
        return $this->belongsTo('App\Models\Room', 'room_id');
    }

    public function customer(){
        return $this->belongsTo('App\Models\Customer', 'customer_id');
    }

    public function getCreatedAtAttribute($value){
        return Carbon::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s', strtotime($value)))->diffForHumans();
    }


    public function likes(){
        return $this->belongsToMany('App\Models\Customer', 'post_likes', 'post_id', 'customer_id');
    }

    public function replies(){
        return $this->hasMany('App\Models\Post', 'parent_id');
    }

    public function shared(){
        return $this->hasMany('App\Models\Post', 'shared_post_id');
    }

    public function mentions(){
        return $this->belongsToMany('App\Models\Customer', 'post_mentions', 'post_id', 'customer_id');
    }

    public static function get_like_status(&$feeds, $user){
        $fids=$feeds->map(function($element){
            return $element->id;
        })->toArray();

        $fids=Post::whereIn('id', $fids)
            ->select('id')
            ->whereHas('likes', function($likes) use($user){
                $likes->where('customers.id', $user->id);
            })->get()->map(function($element){
                return $element->id;
            })->toArray();

        foreach($feeds as $f){
            $f->is_liked=(in_array($f->id, $fids)?1:0);
            if($user->id!=$f->customer->id)
                $f->options_type='other';
            else
                $f->options_type='self';
        }
//        $feeds=$feeds->map(function($element)use($fids){
//
//            return $element;
//        });
    }

    public static function getReportStatus(&$feeds, $user){

        $reported=$user->reported->map(function($element){
            return $element->id;
        });

        foreach($feeds as $f){
            if(in_array($f->customer->id, $reported))
                $f->is_reported=1;
            else
                $f->is_reported=0;
        }

    }


    public static function applyDateSearchFilter($feeds,$type,$date_start,$date_end,$search_term){

        if($search_term)
            $feeds=$feeds->where('content', 'like', '%'.$search_term.'%');

        switch($type){
            case 'hourly':
                return $feeds->where('posts.created_at', '>=', date('Y-m-d H:i:s', strtotime('-1 hour')));
            case 'weekly':return $feeds->where('posts.created_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')));
            case 'custom':return $feeds->where('posts.created_at', '>=', $date_start)
                ->where('posts.created_at', '<=', $date_end);
            default:return $feeds;
        }
    }

    public static function getMentionsList($feeds){
        $mentions=[];
        $mentions_ids=[];
        foreach ($feeds as $feed)
        {
            $f_mentions=$feed->mentions;
            foreach($f_mentions as $m){
                if(!in_array($m->id, $mentions_ids)){
                    $mentions_ids[]=$m->id;
                    $mentions[]=[
                        'id'=>'@#'.$m->id.'#',
                        'name'=>$m->name
                    ];
                }

            }
        }

        return $mentions;

    }

}
