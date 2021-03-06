<?php

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

Route::get('/info', function () {
    phpinfo();
});

//微信
Route::get('index', 'Weixin\WxController@index');
Route::any('index', 'Weixin\WxController@wxEvent');
Route::any('wxEvent', 'Weixin\WxController@wxEvent');


Route::get('token', 'Weixin\WxController@token');
Route::get('text', 'Weixin\WxController@text');
Route::get('getuser', 'Weixin\WxController@getuser');
Route::post('menu', 'Weixin\WxController@menu');
Route::post('news', 'Weixin\WxController@news');

Route::post('sendtext', 'Weixin\WxController@sendtext');
Route::get('send', 'Weixin\WxController@send');


//微信支付
Route::get('test','Weixin\WxPayController@test');           //支付
Route::post('notify','Weixin\WxPayController@notify');       //微信支付回调地址





