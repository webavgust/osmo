<?php

Route::group(['prefix' => 'orders', 'middleware' => ['ajax.api']], function () {
    Route::get('/list_table', 'Api\ApiOrderController@list_table')->name('api.order.list');
    Route::post('/filter', 'Api\ApiOrderController@filter')->name('api.order.filter');
    Route::post('/preset', 'Api\ApiOrderController@preset')->name('api.order.set_preset');
    Route::get('/filter', 'Api\ApiOrderController@filterRemove')->name('api.order.filter.remove');
    Route::post('/days_calc/{order}', 'Api\ApiOrderController@daysCalc')->name('api.order.filter.days_calc');
    Route::post('/{order}/set/curator', 'Api\ApiOrderController@setCurator')->middleware('can:order_tech_leader')->name('api.order.set.curator');
    Route::post('/{order}/set/status', 'Api\ApiOrderController@setStatus')->middleware('can:order_tech_leader')->name('api.order.set.status');


    Route::get('/list_free', 'Api\ApiOrderController@list_free')->name('api.order.list_free');
    Route::post('/sync/all', 'Api\ApiOrderController@sync_all')->name('api.order.sync_all');
});

