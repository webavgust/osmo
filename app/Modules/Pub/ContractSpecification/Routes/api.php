<?php

Route::group(['prefix' => 'contract_specs', 'middleware' => ['ajax.api']], function () {
    Route::put('/create/{contract}', [\App\Modules\Pub\ContractSpecification\Controllers\Api\ApiContractSpecificationController::class, 'store'])->name('api.contract_spec.create');
    Route::post('/update/{spec}', [\App\Modules\Pub\ContractSpecification\Controllers\Api\ApiContractSpecificationController::class, 'update'])->name('api.contract_spec.update');
    Route::delete('/delete/{spec}', [\App\Modules\Pub\ContractSpecification\Controllers\Api\ApiContractSpecificationController::class, 'delete'])->name('api.contract_spec.delete');
    Route::post('/set_project_configuration/{spec}', [\App\Modules\Pub\ContractSpecification\Controllers\Api\ApiContractSpecificationController::class, 'set_project_configuration'])->name('api.contract_spec.set_project_configuration');
});
