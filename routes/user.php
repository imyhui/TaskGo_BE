<?php

Route::get('/captcha/{mobile}','UserController@sendMessage');
Route::post('/captcha/check','UserController@checkCaptcha');

Route::post('/register', 'UserController@register');
Route::post('/login', 'UserController@login');
Route::post('/loginByWx', 'UserController@loginByWx');

//Route::get('/user/checkToken/{token}','UserController@checkToken');
Route::post('/password/forgot','UserController@forgotPassword');

Route::group(['middleware' => 'token'], function() {
    Route::get('/pay/money/true','UserController@makeMoneyComeTrue');
    Route::post('/password/reset', 'UserController@resetPassword');

    Route::get('/userInfo', 'UserController@getUserInfo');
    Route::get('/userInfo/{user_id}', 'UserController@getUserInfoById');

    Route::post('/userInfo', 'UserController@updateUserInfo');
    Route::post('/userInfo/auth', 'UserController@addAuthInfo');
    Route::post('/userInfo/wx', 'UserController@bingWx');
    Route::post('/userInfo/bank', 'UserController@bindBankCard');


    Route::get('/follower', 'UserController@getFollowers');
    Route::get('/following', 'UserController@getFollowings');
    Route::get('/follow/{follower_id}', 'UserController@followUser');
    Route::get('/unFollow/{follower_id}', 'UserController@unFollowUser');

    Route::post('/advice','UserController@addAdvice');

    Route::get('/me/tasks/master/{status}','TaskController@showMyTasksByStatus');
    Route::get('/me/tasks/accept/{status}','TaskController@showMyAcceptTasksByStatus');

    Route::get('/token','UserController@refreshToken');
});