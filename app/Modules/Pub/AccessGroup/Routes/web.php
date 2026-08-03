<?php

//Route::prefix('access')->name('access')->middleware(['can:access_view'])->group(function() {
//    Route::get('list', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'list'])->name('.list');
//});

Route::group(['prefix' => 'access_group', 'middleware' => ['can:access_create']], function () {
    Route::get('/create', 'AccessGroupController@create')->name('access_group.create');
    Route::post('/', 'AccessGroupController@store')->name('access_group.store');
    Route::get('/edit/{group?}', 'AccessGroupController@edit')->name('access_group.edit');
    Route::put('/{group}', 'AccessGroupController@update')->name('access_group.update');
    Route::delete('/{group}', 'AccessGroupController@destroy')->name('access_group.delete');
});
