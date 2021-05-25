<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;
    protected $table='documents';

    protected $fillable = ['image', 'entity_id', 'entity_type'];

    protected $hidden = ['deleted_at','updated_at','created_at'];

    public function getImageAttribute($value){
        if($value)
            return Storage::url($value);
        return null;
    }

    public function entity(){
        return $this->morphTo();
    }

}
