<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Notification extends Model
{
    protected $table='notifications';

    protected $hidden = ['deleted_at','updated_at'];

    protected $fillable=['user_id', 'title', 'description', 'type','type_id', 'var1','var2','var3', 'seen_at', 'image'];

    public function getCreatedAtAttribute($value){
        return date('m/d/Y h:ia', strtotime($value));
    }

    public function getImageAttribute($value){
        if($value)
            return Storage::url($value);
        return Storage::url('customers/default.jpeg');
    }
}
