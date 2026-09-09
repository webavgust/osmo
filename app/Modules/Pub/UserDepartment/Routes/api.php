<?php

Route::group(['prefix' => 'user_department', 'middleware' => ['ajax.api']], function () {
    Route::post('/agreement_save/{userDepartment}', [\App\Modules\Pub\UserDepartment\Controllers\Api\ApiUserDepartmentController::class, 'agreement_save'])->name('api.user_department.agreement_save');
});

