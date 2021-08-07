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
$api->post('resend-otp', 'MobileApps\Auth\OtpController@resend');
$api->post('reset-password', 'MobileApps\Auth\ForgotPasswordController@reset');
$api->post('update-password', 'MobileApps\Auth\ForgotPasswordController@update');
$api->post('login', 'MobileApps\Auth\LoginController@login');
$api->post('fb-login', 'MobileApps\Auth\LoginController@facebookLogin');
$api->post('google-login', 'MobileApps\Auth\LoginController@googleLogin');
//test comment again
$api->get('check-login-status', 'MobileApps\Auth\LoginController@loginCheck');

$api->group(['middleware' => ['customer-api-auth']], function ($api) {

    //$api->get('logout', 'MobileApps\Auth\LoginController@logout');

    $api->get('home', 'MobileApps\Api\HomeController@index');
    $api->get('feeds', 'MobileApps\Api\PostController@feeds');

    $api->post('post/create', 'MobileApps\Api\PostController@store');
    $api->post('post-stock-search', 'MobileApps\Api\SearchController@searchStocks');
    $api->get('post/like/{post_id}', 'MobileApps\Api\PostController@likePost');
    $api->get('post/details/{post_id}', 'MobileApps\Api\PostController@postDetails');
    $api->post('post/reply', 'MobileApps\Api\ReplyController@store');
    $api->get('reply/details/{post_id}', 'MobileApps\Api\ReplyController@replyDetails');
    $api->post('mentions-list', 'MobileApps\Api\SearchController@mentionsList');

    $api->get('price-alerts', 'MobileApps\Api\PriceAlertController@myalerts');
    $api->post('add-alert', 'MobileApps\Api\PriceAlertController@add');
    $api->get('delete-alert/{id}', 'MobileApps\Api\PriceAlertController@delete');
    $api->post('alert-search', 'MobileApps\Api\PriceAlertController@search');

    $api->get('stock-details/{stock_id}', 'MobileApps\Api\StockController@details');


    $api->get('get-profile', 'MobileApps\Api\ProfileController@getProfile');
    $api->post('set-profile', 'MobileApps\Api\ProfileController@setProfile');

    $api->get('profile/details/{id?}', 'MobileApps\Api\ProfileController@details');

    $api->get('follow/{id}', 'MobileApps\Api\FollowController@follow');
    $api->get('followers/{id}', 'MobileApps\Api\FollowController@followers');
    $api->get('followings/{id}', 'MobileApps\Api\FollowController@followings');
    $api->get('block/profile/{profile_id}', 'MobileApps\Api\ProfileController@block');
    $api->post('report/profile/{profile_id}', 'MobileApps\Api\ProfileController@report');


    $api->get('get-notification-settings', 'MobileApps\Api\ProfileController@getNotificationSettings');
    $api->post('update-notification-settings', 'MobileApps\Api\ProfileController@setNotificationSettings');

    $api->get('watchlist', 'MobileApps\Api\WatchlistController@index');
    $api->get('watchlist/add/{stock_id}', 'MobileApps\Api\WatchlistController@addToWatchList');
    $api->get('watchlist/remove/{stock_id}', 'MobileApps\Api\WatchlistController@removeFromWatchList');

    $api->get('rooms', 'MobileApps\Api\RoomController@index'); //params:type=free/paid/myrooms
    $api->post('room/create', 'MobileApps\Api\RoomController@add');
    $api->get('room/members/{room_id}', 'MobileApps\Api\RoomController@members');
    $api->get('room/delete/{room_id}', 'MobileApps\Api\RoomController@delete');
    $api->get('room/details/{room_id}', 'MobileApps\Api\RoomController@roomPosts');
    $api->get('room/join/{room_id}', 'MobileApps\Api\RoomController@joinRoom');
    $api->get('room/leave/{room_id}', 'MobileApps\Api\RoomController@leaveRoom');



    $api->get('wallet-home', 'MobileApps\Api\WalletController@home');
    $api->get('wallet-history', 'MobileApps\Api\WalletController@index');
    //$api->post('recharge','MobileApps\Api\WalletController@addMoney');

    $api->get('chats', 'MobileApps\Api\ChatController@chatlist');
    $api->get('chats/{id}', 'MobileApps\Api\ChatController@chatDetails');
    $api->get('polling-chat/{id}', 'MobileApps\Api\ChatController@chatPolling');
    //$api->get('polling-list/{id}', 'MobileApps\Api\ChatController@listPolling');
    $api->post('send-message/{id}', 'MobileApps\Api\ChatController@send');
    $api->post('chat-search', 'MobileApps\Api\ChatController@search');

    $api->get('notify-me/{profile_id}', 'MobileApps\Api\ProfileController@notify_me');

});

$api->get('stock-chart/{stock_id}', 'MobileApps\Api\StockController@webview')->name('stock.webview');


$api->post('verify-payment', 'MobileApps\Api\PaymentController@verifyPayment');

$api->get('notifications', 'MobileApps\Api\NotificationController@index');
$api->get('room-rules', 'MobileApps\Api\RoomController@room_rules');
