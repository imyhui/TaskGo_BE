<?php

Route::post('/water','WaterController@buyWater')->middleware('token');
Route::get('/waters','WaterController@showWaters')->middleware('token');
Route::post('/tasks/accept/waters','WaterController@acceptTask')->middleware('token');
Route::post('/pay/water/notify','WaterController@notify');
Route::get('/water/finish/{waterId}','WaterController@finishTask')->middleware('token');
Route::get('/water/reject/{waterId}','WaterController@rejectFinishTask')->middleware('token');
Route::get('/water/apply','WaterController@applyFinishTask');
Route::get('/water/status/{waterId}','WaterController@getWaterPayStatus');
Route::get('/water/return/cards/{waterId}','WaterController@returnCards');
Route::post('/refund/water/{waterId}','WaterController@refundNotify');
Route::get('/water/close/{waterId}','WaterController@closeOrder')->middleware('token');