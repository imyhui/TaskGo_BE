<?php


Route::get('/messages','MessageController@getMessages')->middleware('token');
Route::post('/messages/read','MessageController@readMessages');
Route::post('/messages/delete','MessageController@deleteMessages');
