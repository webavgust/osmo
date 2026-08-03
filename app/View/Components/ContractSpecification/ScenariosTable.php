<?php

namespace App\View\Components\ContractSpecification;


use App\Modules\Pub\Client\Models\Client;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\View\Component;

class ScenariosTable extends Component
{
    public function __construct(ContractSpecification $specification)
    {
        $this->specification = $specification;
    }


    public function render()
    {
        return view('components.contract_specification.scenarios_table', [
            'spec_scenarios' => $this->specification->contract_specification_scenarios,
            'scenarios' => ScenarioRepository::getAll(),
        ]);
    }
}
