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

$api->get('chat', function(){
    return view('chat');
});

$api->get('app-version', 'MobileApps\Api\VersionController@version');

//$api->post('login', 'MobileApps\Auth\LoginController@login');
$api->post('login-with-otp', 'MobileApps\Auth\LoginController@loginWithOtp');
$api->post('register', 'MobileApps\Auth\RegisterController@register');
//$api->post('forgot', 'MobileApps\Auth\ForgotPasswordController@forgot');
$api->post('verify-otp', 'MobileApps\Auth\OtpController@verify');
$api->post('resend-otp', 'MobileApps\Auth\OtpController@resend');
//$api->post('update-password', 'MobileApps\Auth\ForgotPasswordController@updatePassword');
$api->post('fb-login', 'MobileApps\Auth\LoginController@facebookLogin');
$api->post('gmail-login', 'MobileApps\Auth\LoginController@gmailLogin');
//test comment again

$api->group(['middleware' => ['customer-api-auth']], function ($api) {

    $api->get('logout', 'MobileApps\Auth\LoginController@logout');

    $api->get('chats', 'MobileApps\Api\ChatController@chathistory');
    $api->post('start-chat/{store_id?}', 'MobileApps\Api\ChatController@startChat');

    $api->get('chat-messages/{id}', 'MobileApps\Api\ChatMessageController@chatDetails');
    $api->post('send-message/{id}', 'MobileApps\Api\ChatMessageController@send');

    $api->get('wallet-history', 'MobileApps\Api\WalletController@index');
    $api->post('recharge','MobileApps\Api\WalletController@addMoney');


});


$api->post('verify-payment', 'MobileApps\Api\PaymentController@verifyPayment');

$api->get('notifications', 'MobileApps\Api\NotificationController@index');
