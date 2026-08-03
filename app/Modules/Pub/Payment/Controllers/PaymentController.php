<?php

namespace App\Modules\Pub\Payment\Controllers;

use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Services\EducationApplicationListFilterService;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Models\CompanyGrade;
use App\Modules\Pub\Company\Models\CompanyType;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Company\Services\CompanyListFilterService;
use App\Modules\Pub\Company\Services\CompanyService;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Company\Requests\CompanyUpdateRequest;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class PaymentController extends Controller
{
    // BOXES
    public function box_control(ContractSpecification $spec)
    {
        $users = UserRepository::getAllWithTrashed();

        $template = View::make('pub.payments.boxes.control', [
            'title' => 'Редактирование платежей',
            'spec' => $spec,
            'users' => $users,
        ]);

        return $template;
    }

    public function box_past(Company  $company)
    {
        $template = View::make('pub.payments.boxes.past', [
            'title' => 'Полученные платежи',
            'company' => $company,
        ]);

        return $template;
    }

    public function box_future(Company  $company)
    {
        $template = View::make('pub.payments.boxes.future', [
            'title' => 'Будущие платежи',
            'company' => $company,
        ]);

        return $template;
    }
}
