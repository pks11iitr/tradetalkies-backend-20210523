<?php

use Illuminate\Http\Request;
$api = app('Dingo\Api\Routing\Router');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api->get('app-version', 'MobileApps\Api\VersionController@version');
$api->post('register', 'MobileApps\Auth\RegisterController@register');
$api->post('verify-otp', 'MobileApps\Auth\OtpController@verify');
$api->post('send-otp', 'MobileApps\Auth\OtpController@resend');
$api->post('reset-password', 'MobileApps\Auth\ForgotPasswordController@reset');
$api->post('update-password', 'MobileApps\Auth\ForgotPasswordController@update');
$api->post('login', 'MobileApps\Auth\LoginController@login');
$api->post('fb-login', 'MobileApps\Auth\LoginController@facebookLogin');
$api->post('google-login', 'MobileApps\Auth\LoginController@googleLogin');
//test comment again

$api->group(['middleware' => ['customer-api-auth']], function ($api) {

    $api->get('logout', 'MobileApps\Auth\LoginController@logout');

    $api->get('home', 'MobileApps\Auth\HomeController@index');

    $api->get('watchlist', 'MobileApps\Auth\WatchlistController@index');
    $api->get('watchlist/add/{stock_id}', 'MobileApps\Auth\WatchlistController@addToWatchList');
    $api->get('watchlist/remove/{stock_id}', 'MobileApps\Auth\WatchlistController@removeFromWatchList');

    $api->get('feeds', 'MobileApps\Auth\PostController@feeds');

    $api->get('rooms', 'MobileApps\Auth\RoomController@index'); //params:type=free/paid/myrooms
    $api->post('room/create', 'MobileApps\Auth\RoomController@add');
    $api->get('room/members/{room_id}', 'MobileApps\Auth\RoomController@members');
    $api->get('room/delete/{room_id}', 'MobileApps\Auth\RoomController@delete');

    $api->post('post/create', 'MobileApps\Auth\PostController@store');

    $api->get('wallet-history', 'MobileApps\Api\WalletController@index');
    $api->post('recharge','MobileApps\Api\WalletController@addMoney');

    $api->get('chats', 'MobileApps\Api\ChatController@chathistory');
    $api->post('start-chat/{store_id?}', 'MobileApps\Api\ChatController@startChat');

    $api->get('chat-messages/{id}', 'MobileApps\Api\ChatMessageController@chatDetails');
    $api->post('send-message/{id}', 'MobileApps\Api\ChatMessageController@send');




});


$api->post('verify-payment', 'MobileApps\Api\PaymentController@verifyPayment');

$api->get('notifications', 'MobileApps\Api\NotificationController@index');
