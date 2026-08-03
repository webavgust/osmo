<?php

// компоненты
Route::group(['prefix' => 'contract_specification_scenario/component'], function () {
    Route::get('/scenario_table_row', function (\Illuminate\Http\Request $request) {
        return View::make('components.contract_specification.scenarios_table_row', [
            'scenarios' => \App\Modules\Pub\Scenario\Repository\ScenarioRepository::getAll(),
            'num' => $request->input('num') ?? 1
        ]);
    })->name('contract_specification_scenario.component.scenario_table_row');
});
