<?php

Route::group(['prefix' => 'settings', 'middleware' => ['ajax.api']], function () {
    Route::post('/set', 'Api\UserSettings@set')->name('settings.set');
});



