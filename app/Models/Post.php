<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory, DocumentUploadTrait;

    protected $table='posts';

    protected $fillable=['parent_id', 'stock_id', 'customer_id', 'content'];

    public function stocks(){
        return $this->belongsToMany('App\Models\Post', 'post_id', 'stock_id');
    }


}
