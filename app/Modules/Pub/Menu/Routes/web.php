<?php

//Route::prefix('access')->name('access')->middleware(['can:access_view'])->group(function() {
//    Route::get('list', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'list'])->name('.list');
//});

Route::group(['prefix' => 'menu', 'middleware' => ['can:menu_view']], function () {
    Route::get('/', 'MenuController@index')->name('menu.index');

    Route::group(['middleware' => 'can:menu_control'], function() {
          Route::post('/update/{menu?}', 'MenuController@update')->name('menu.update');
//        Route::post('/{group}', 'MenuController@store')->name('menu.store');
//        Route::get('/test', 'MenuController@test')->name('menu.test');
//        Route::get('/edit/{access}', 'MenuController@edit')->name('menu.edit');
//        Route::put('/edit/{access}', 'MenuController@update')->name('menu.update');
//        Route::delete('/{access}', 'MenuController@destroy')->name('menu.delete');
    });
});
