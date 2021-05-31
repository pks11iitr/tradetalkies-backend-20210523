<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAlert extends Model
{
    use HasFactory;

    protected $table='price_alerts';

    protected $fillable=['stock_id', 'user_id', 'alert_price'];


    public function stock(){
        return $this->belongsTo('App\Models\Stock', 'stock_id');
    }

}
