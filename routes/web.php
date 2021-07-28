<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth'])->name('dashboard');

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
Route::group(['middleware'=>['auth', 'acl'], 'is'=>'admin'], function() {


//    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
//
//    Route::get('/dashboard', 'SuperAdmin\DashboardController@index')->name('home');
//
//
//    Route::group(['prefix' => 'setting'], function () {
//
//        Route::get('/','SuperAdmin\SettingController@index')->name('setting.list');
//        Route::get('edit/{id}','SuperAdmin\SettingController@edit')->name('setting.edit');
//        Route::post('update/{id}', 'SuperAdmin\SettingController@update')->name('setting.update');
//    });
//
//
//
//    Route::group(['prefix' => 'customer'], function (){
//        Route::get('/','SuperAdmin\CustomerController@index')->name('customer.list');
//        Route::get('edit/{id}','SuperAdmin\CustomerController@edit')->name('customer.edit');
//        Route::post('update/{id}','SuperAdmin\CustomerController@update')->name('customer.update');
//        Route::get('export{id}','SuperAdmin\CustomerController@export')->name('customer.export');
//
//        Route::post('add-money/{id}','SuperAdmin\CustomerController@addMoney')->name('customer.wallet.add');
//        Route::get('history-list/{id}','SuperAdmin\CustomerController@transaction')->name('customer.tranaction.list');
//
//    });
//
//
//
//
//    Route::group(['prefix'=>'chats'], function(){
//        Route::get('/','SuperAdmin\ChatController@index')->name('chat.list');
//        Route::get('chats/{id}','SuperAdmin\ChatController@chats')->name('order.chats.details');
//    });
//
//    Route::group(['prefix'=>'checkin'], function(){
//        Route::get('/','SuperAdmin\CheckinController@index')->name('checkin.list');
//        Route::get('export{id}','SuperAdmin\CheckinController@export')->name('checkin.export');
//
//    });
//
//
//    Route::group(['prefix'=>'notification'], function(){
//            Route::get('create','SuperAdmin\NotificationController@create')->name('notification.create');
//            Route::post('store','SuperAdmin\NotificationController@store')->name('notification.store');
//    });

});

require __DIR__.'/auth.php';
