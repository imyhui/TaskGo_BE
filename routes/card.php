<?php

Route::group(['middleware' => 'token'], function () {
    Route::get('/mycards', 'CardController@getMyCards');

    Route::get('/cards', 'CardController@getAllCard');
    Route::get('/cards/{cardId}', 'CardController@getCardById');
    Route::group(['middleware' => 'admin'], function () {
        Route::post('/cards', 'CardController@createCard');
        Route::put('/cards', 'CardController@updateCard');
    });
});