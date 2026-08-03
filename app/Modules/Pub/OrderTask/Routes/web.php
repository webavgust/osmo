<?php

use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use Illuminate\Support\Facades\View;


Route::group(['prefix' => 'order_task', 'middleware' => ['can:order_task_view']], function () {
    Route::get('/', 'OrderTaskController@index')->name('order_task.index');

    Route::group(['middleware' => 'can:order_task_edit'], function() {

        // создание
        // Route::get('/init/{contract_id}/{contract_sub_id}/{block_id}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step1_create'])->name('order_task.create_step1');
        // Route::get('/init/{contract_id}/{block_id}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step1_create_fast'])->name('order_task.create_step1');

        Route::get('/init/{block_id}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'create_from_evaluation'])->name('order_task.create_from_evaluation');

        Route::post('/add/save/1', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step1_save'])->name('order_task.step1_save');
        // >>>
        Route::get('/add/{order_task}/step/2', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step2_create'])->name('order_task.create_step2');
        Route::post('/add/{order_task}/save/2', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step2_save'])->name('order_task.step2_save');

        // редактирование
        Route::get('/edit/{order_task?}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step1_edit'])->name('order_task.edit_step1');
        Route::post('/edit/{order_task}/step/1/update', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step1_update'])->name('order_task.step1_update');
        // >>>
        Route::get('/edit/{order_task}/step/2', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step2_edit'])->name('order_task.edit_step2');
        Route::post('/edit/{order_task}/step/2/update', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'step2_update'])->name('order_task.step2_update');
    });
});


// копирование
Route::group(['prefix' => 'order_task', 'middleware' => ['can:order_task_copy']], function () {
    Route::get('/copy/{order}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'copy_form'])->name('order_task.copy.form');
});

// согласование форма
Route::group(['prefix' => 'order_task', 'middleware' => ['can:order_task_agree']], function () {
    Route::get('/agreement/{order_task?}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'agreement_form'])->name('order_task.agreement.form');
    Route::get('/agreement/view/{order_task?}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'agreement_view'])->name('order_task.agreement.view');
});

// привязка
Route::group(['prefix' => 'order_task', 'middleware' => ['can:order_task_copy']], function () {
    Route::get('/attach/{order_task?}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'attach_form'])->name('order_task.attach.form');
});

// просмотр
Route::group(['prefix' => 'order_task', 'middleware' => ['can:order_task_view']], function () {
    Route::get('/detail/{order_task?}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'detail'])->name('order_task.detail');
    Route::get('/sidebar/view/{order_task?}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'sidebar_view'])->name('order_task.sidebar_view');

    // boxes
    Route::get('/box/set_samplers/{order_task}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'box_set_samplers'])->name('order_task.box_set_samplers');
    Route::get('/box/summary/{order_task}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'box_summary'])->name('order_task.box_summary');
    Route::get('/box/visits/{order_task}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'box_visits'])->name('order_task.box_visits');

    // sidebars
    Route::get('/sidebar/set_samplers/{target_type}/{target_id}', [\App\Modules\Pub\OrderTask\Controllers\OrderTaskController::class, 'sidebar_set_samplers'])->name('order_task.sidebar_set_samplers');

});




// компоненты
Route::group(['prefix' => 'order_task/component', 'middleware' => ['can:order_task_edit']], function () {
    Route::get('/object', function(\Illuminate\Http\Request $request) {
        return View::make('components.order_task.create.object_row', ['num' => $request->input('num')]);
    })->name('order_task.component.object');

    Route::get('/address', function(\Illuminate\Http\Request $request) {
        return View::make('components.order_task.create.address_row', ['num' => $request->input('num'), 'parent' => $request->input('uid')]);
    })->name('order_task.component.address');

    Route::get('/point', function(\Illuminate\Http\Request $request) {
        return View::make('components.order_task.create.point_row', ['num' => $request->input('num'), 'parent' => $request->input('uid')]);
    })->name('order_task.component.point');



    Route::get('/measure/', function(\Illuminate\Http\Request $request) {
        if(!empty($request->input('point_id'))) {
            $point = \App\Modules\Pub\OrderTaskPoint\Models\OrderTaskPoint::findOrFail($request->input('point_id'));
            $object = $point->address->object;
        } elseif(!empty($request->input('object_id'))) {
            $point = new stdClass();
            $point->id = $request->input("point_uid");
            $object = \App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject::findOrFail($request->input('object_id'));
        }
        $measures = LabMeasure::getTree($object->lab_object->lab_measures?->pluck('id')->toArray() ?? []);
        return View::make('components.order_task.create.measure_row', compact('measures', 'point'));
    })->name('order_task.component.measure');

    Route::get('/service/', function(\Illuminate\Http\Request $request) {
        $services = \App\Modules\Pub\Service\Models\Service::all();
        $object = \App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject::findOrFail($request->input('object_id'));
        return View::make('components.order_task.create.service_row', compact('services', 'object'));
    })->name('order_task.component.service');
});
