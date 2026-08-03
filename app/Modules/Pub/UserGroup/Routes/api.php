<?php

Route::group(['prefix' => 'users', 'middleware' => ['ajax.api']], function () {
    Route::get('/list_table', 'Api\UserGroupController@list_table')->name('api.users.list');
});
