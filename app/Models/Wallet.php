<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Storage;

class Wallet extends Model
{
    protected $table='wallet';

    protected $fillable=['refid','type','amount','description','iscomplete', 'order_id_response', 'payment_id', 'payment_id_response','user_id'];

    protected $hidden=[ 'updated_at', 'deleted_at','iscomplete', 'order_id_response', 'payment_id', 'payment_id_response', 'order_id'];

    public function customer()
    {
        return $this->belongsTo('App\Models\Customer', 'user_id');
    }


    public static function balance($userid){
        $wallet=Wallet::where('user_id', $userid)
            ->where('iscomplete', true)
            ->select(DB::raw('sum(amount) as total'), 'type')
            ->groupBy('type')
            ->get();
        $balances=[];
        foreach($wallet as $w){
            $balances[$w->type]=$w->total;
        }
        return ($balances['Credit']??0)-($balances['Debit']??0);
    }

    public static function updatewallet($userid, $description, $type, $amount, $amount_type, $orderid=null){
        Wallet::create([
            'user_id'=>$userid,
            'description'=>$description,
            'type'=>$type,
            'iscomplete'=>1,
            'amount'=>$amount,
            'refid'=>date('YmdHis')]);
    }


    // deduct amount from wallet if applicable
    public static function payUsingWallet($order){
        $walletbalance=Wallet::balance($order->user_id);
        $fromwallet=($order->total>=$walletbalance)?$walletbalance:$order->total;
        $order->usingwallet=true;
        $order->fromwallet=$fromwallet;
        if($order->total-$fromwallet>0){
            $paymentdone='no';
        }else{
            Wallet::updatewallet($order->user_id,'Paid for Order ID:'.$order->refid, 'Debit',$fromwallet, 'CASH');
            $order->payment_status='paid';
            $paymentdone='yes';
        }
        $order->save();
        return [
            'paymentdone'=>$paymentdone,
            'fromwallet'=>$fromwallet
        ];
    }

}
