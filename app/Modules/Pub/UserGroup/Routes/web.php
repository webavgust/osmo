<?php

Route::group(['prefix' => 'user_group', 'middleware' => ['auth', 'can:users_groups_view_catalog']], function () {
    Route::get('/list', 'UserGroupController@list')->name('user_group.list');
    Route::get('/detail/{userGroup}', 'UserGroupController@detail')->name('user_group.detail');

});
