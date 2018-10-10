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

include 'user.php';
include 'card.php';
include 'water.php';
include 'message.php';
include 'version.php';

Route::get('/test/get/rsa','TestController@testGetRsa');
Route::get('/test/tobank','TestController@testToBank');
Route::get('/test/refund','TestController@testReFund');