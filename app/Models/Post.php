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

    protected $fillable=['parent_id', 'customer_id', 'content', 'room_id', 'views'];

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

}
