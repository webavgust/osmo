<?php

Route::group(['prefix' => 'orders', 'middleware' => ['can:order_view']], function () {
    Route::get('/', 'OrderController@index')->name('order.index');
    Route::get('/{order?}', 'OrderController@detail')->name('order.detail');
//    Route::get('/create', 'OrderController@create')->name('orders.create');
//    Route::post('/', 'OrderController@store')->name('orders.store');
//    Route::get('/{order}', 'OrderController@show')->name('orders.read');
//    Route::get('/edit/{order}', 'OrderController@edit')->name('orders.edit');
//    Route::put('/{order}', 'OrderController@update')->name('orders.update');
//    Route::delete('/{order}', 'OrderController@destroy')->name('orders.delete');
});

Route::group(['prefix' => 'orders', 'middleware' => ['can:order_task_attach']], function () {
    Route::get('/attach/{order}', 'OrderController@attach_form')->name('order.attach_task.form');
});



