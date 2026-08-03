<?php

// работа с таблицей
Route::group(['prefix' => 'order_task', 'middleware' => ['ajax.api', 'can:order_task_view']], function () {
    Route::get('/list_table', [\App\Modules\Pub\OrderTask\Controllers\Api\ApiOrderTaskController::class, 'list_table'])->name('api.order_task.list_table');
    Route::post('/filter', 'Api\ApiOrderTaskController@filter')->name('api.order_task.filter');
    Route::get('/filter', 'Api\ApiOrderTaskController@filterRemove')->name('api.order_task.filter.remove');

    Route::group(['middleware' => ['can:order_task_edit']], function() {
        Route::post('/create/{order}', 'Api\ApiOrderTaskController@create')->name('api.order_task.create');
    });
    Route::post('/set_samplers/{order}', [\App\Modules\Pub\OrderTask\Controllers\Api\ApiOrderTaskController::class, 'set_samplers'])->name('api.order_task.set_samplers');
});

//
Route::group(['prefix' => 'order_task', 'middleware' => ['ajax.api', 'can:order_task_submit']], function () {
    Route::post('/status_work/{order}', 'Api\ApiOrderTaskController@status_work')->name('api.order_task.status_work');
});

// копирование ТЗ
Route::group(['prefix' => 'order_task', 'middleware' => ['ajax.api', 'can:order_task_copy']], function () {
    Route::post('/copy/{order}', 'Api\ApiOrderTaskController@copy')->name('api.order_task.copy');
    Route::get('/list', 'Api\ApiOrderTaskController@list')->name('api.order_task.list');
});

// согласование ТЗ
Route::group(['prefix' => 'order_task', 'middleware' => ['ajax.api', 'can:order_task_agree']], function () {
    Route::post('/agree/{order_task}', [\App\Modules\Pub\OrderTask\Controllers\Api\ApiOrderTaskController::class, 'agree'])->name('api.order_task.agree');
    Route::post('/agree_decision/{order_task}', [\App\Modules\Pub\OrderTask\Controllers\Api\ApiOrderTaskController::class, 'agree_decision'])->name('api.order_task.agree_decision');
});

// присоединение ТЗ к заявке
Route::group(['prefix' => 'order_task', 'middleware' => ['ajax.api', 'can:order_task_attach']], function () {
    Route::post('/attach/{order_task}', 'Api\ApiOrderTaskController@attach')->name('api.order_task.attach');
    Route::post('/attach_order/{order}', 'Api\ApiOrderTaskController@attach_order')->name('api.order_task.attach_order');
    Route::get('/list_free', 'Api\ApiOrderTaskController@list_free')->name('api.order_task.list_free');
});

// проверка из портала
Route::group(['prefix' => 'order_task', 'middleware' => ['portal']], function () {
    Route::get('/check/{contract_id}/{contract_sub_id}/{order_task_slug}/{iteration?}', 'Api\ApiOrderTaskController@portal_check')->name('api.portal_check');
});

// отмена ТЗ
Route::group(['prefix' => 'order_task', 'middleware' => ['ajax.api']], function () {
    Route::post('/cancel/{order_task}', 'Api\ApiOrderTaskController@cancel')->name('api.order_task.cancel');
    Route::post('/recreate/{order_task}', 'Api\ApiOrderTaskController@recreate')->name('api.order_task.recreate');
});

Route::post('/start_working/{order_task}', [\App\Modules\Pub\OrderTask\Controllers\Api\ApiOrderTaskController::class, 'start_working'])->middleware(['ajax.api'])->name('api.order_task.start_working');
Route::post('/finish/{order_task}', [\App\Modules\Pub\OrderTask\Controllers\Api\ApiOrderTaskController::class, 'finish'])->middleware(['ajax.api'])->name('api.order_task.finish');



