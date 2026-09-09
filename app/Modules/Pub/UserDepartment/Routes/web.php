<?php

Route::group(['prefix' => 'user_department', 'middleware' => ['auth', 'can:users_departments_view_catalog']], function () {
    Route::get('/list', [\App\Modules\Pub\UserDepartment\Controllers\UserDepartmentController::class, 'list'])->name('user_department.list');
    Route::get('/detail/{userDepartment}', [\App\Modules\Pub\UserDepartment\Controllers\UserDepartmentController::class, 'detail'])->name('user_department.detail');

    // sidebars
    Route::get('/sidebar/agreements/{userDepartment}', [\App\Modules\Pub\UserDepartment\Controllers\UserDepartmentController::class, 'sidebar_agreements'])->name('user_department.sidebar_agreements');

});
