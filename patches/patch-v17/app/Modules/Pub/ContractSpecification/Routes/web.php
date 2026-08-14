<?php


Route::group(['prefix' => 'contract_specs'], function () {
    Route::group([], function () {
        // boxes
        Route::get('/box/add/{contract}', [\App\Modules\Pub\ContractSpecification\Controllers\ContractSpecificationController::class, 'box_add'])->name('contract_spec.box_add');
        Route::get('/box/edit/{spec}', [\App\Modules\Pub\ContractSpecification\Controllers\ContractSpecificationController::class, 'box_edit'])->name('contract_spec.box_edit');
        Route::get('/box/project_configuration/{spec}', [\App\Modules\Pub\ContractSpecification\Controllers\ContractSpecificationController::class, 'box_project_configuration'])->name('contract_spec.box_project_configuration');
        Route::get('/box/proposal/{spec}', [\App\Modules\Pub\ContractSpecification\Controllers\ContractSpecificationController::class, 'box_proposal'])->name('contract_spec.box_proposal');
    });
});
