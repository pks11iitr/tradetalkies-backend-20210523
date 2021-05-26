<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $table='stocks';

    protected $fillable=['name','code', 'price'];

    protected $hidden=['created_at', 'deleted_at', 'updated_at'];

    public function watchlist(){
        return $this->belongsToMany('App\Models\Customer', 'stocks_watchlist', 'stock_id', 'customer_id');
    }
}
