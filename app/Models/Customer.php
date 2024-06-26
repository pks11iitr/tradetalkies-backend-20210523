<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\Fcm\Exceptions\CouldNotSendNotification;
use Tymon\JWTAuth\Contracts\JWTSubject;
use DateTime;
class Customer extends Authenticatable implements JWTSubject
{
    use DocumentUploadTrait, Notifiable, HasFactory;

    protected $table='customers';

    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'image', 'username', 'status','push_likes','push_mentions', 'push_direct_messages', 'push_follows','push_watchlists', 'push_rooms','email_likes', 'email_mentions','email_direct_messages', 'email_follows', 'email_watchlist','email_rooms', 'industry_id', 'about', 'telegram_id', 'twitter_id', 'age'];

    protected $hidden = [
        'password','created_at','deleted_at','updated_at','email','mobile'
    ];

    //protected $appends=['age'];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Specifies the user's FCM token
     *
     * @return string|array
     */
    public function routeNotificationForFcm()
    {
        return $this->notification_token;
    }

    public function notify($instance)
    {
        try{
            app(Dispatcher::class)->send($this, $instance);

        }catch(CouldNotSendNotification $e){

        }

    }

    public function getImageAttribute($value){
        if($value)
            return Storage::url($value);
        return Storage::url('customers/default.jpeg');
    }

    public function getTwitterIdAttribute($value){
        if($value)
            return $value;
        return '';
    }

    public function getAgeAttribute($value){
        if($value)
            return $value;
        return '';
    }

    public function getAboutAttribute($value){
        if($value)
            return $value;
        return '';
    }

    public function getTelegramIdAttribute($value){
        if($value)
            return $value;
        return '';
    }

    public function getIndustryIdAttribute($value){
        if($value)
            return $value;
        return '0';
    }


    public static function getCustomer(Request $request){
        return Customer::where(userId($request), $request->user_id)
            ->first();
    }

    public function watchlist(){
        return $this->belongsToMany('App\Models\Stock', 'stocks_watchlist', 'customer_id', 'stock_id');
    }

    public function followings(){
        return $this->belongsToMany('App\Models\Customer', 'followers', 'follower_id', 'customer_id');
    }

    public function followers(){
        return $this->belongsToMany('App\Models\customer', 'followers', 'customer_id', 'follower_id');
    }

    public function myrooms(){
        return $this->hasMany('App\Models\Room', 'created_by');
    }

    public function rooms(){
        return $this->belongsToMany('App\Models\Room', 'room_members', 'customer_id', 'room_id');
    }


    public function posts(){
        return $this->hasMany('App\Models\Post', 'customer_id');
    }

    public function likes(){
        return $this->belongsToMany('App\Models\Post', 'post_likes', 'customer_id', 'post_id');
    }

    public function blocked(){
        return $this->belongsToMany('App\Models\Customer', 'block_profile', 'user_id', 'profile_id');
    }

    public function blockedBy(){
        return $this->belongsToMany('App\Models\Customer', 'block_profile', 'profile_id', 'user_id');
    }

    public function reported(){
        return $this->belongsToMany('App\Models\Customer', 'report_profile', 'user_id', 'profile_id')->withPivot('reason');
    }

    public function reportedBy(){
        return $this->belongsToMany('App\Models\Customer', 'report_profile', 'profile_id', 'user_id');
    }

    public function notify_me(){
        return $this->belongsToMany('App\Models\Customer', 'post_notify', 'user_id', 'profile_id');
    }


}
