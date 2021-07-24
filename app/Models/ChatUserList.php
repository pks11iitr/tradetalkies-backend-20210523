<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatUserList extends Model
{
    use HasFactory;

    protected $table='chat_users_list';

    protected $fillable=['customer_id', 'chat_user_id', 'last_chat_id'];

    public function listuser(){
        return $this->belongsTo('App\Models\Customer', 'chat_user_id');
    }

}
