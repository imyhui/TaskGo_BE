<?php

Route::get('/version/new','VersionController@getVersionInfo');
Route::post('/version','VersionController@newVersionInfo');