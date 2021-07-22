<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory, DocumentUploadTrait;

    protected $table='chats';

    protected $fillable=['user_1', 'user_2', 'direction', 'message', 'image','is_first_approved','user1_seen_at', 'user2_seen_at'];


    public function user1(){
        return $this->belongsTo('App\Models\Customer', 'user_1');
    }

    public function user2(){
        return $this->belongsTo('App\Models\Customer', 'user_2');
    }

    public function getCreatedAtAttribute($value)
    {
        return date('d/m/Y h:ia', strtotime($value));
    }
}
