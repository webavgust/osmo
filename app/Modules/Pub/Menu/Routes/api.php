<?php

Route::group(['prefix' => 'menu', 'middleware' => ['ajax.api']], function () {
    Route::get('/', 'Api\MenuController@index')->name('api.menu.index');
    Route::post('/update', 'Api\MenuController@update')->name('api.menu.update');
    Route::post('/view/{menu?}', 'Api\MenuController@view')->name('api.menu.view');
});
