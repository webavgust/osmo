<?php

namespace App\Modules\Pub\Partner\Controllers;

use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Services\CompanyService;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Services\EducationApplicationListFilterService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Partner\Models\PartnerType;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Partner\Services\PartnerListFilterService;
use App\Modules\Pub\Partner\Services\PartnerService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Partner\Requests\PartnerUpdateRequest;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class PartnerController extends Controller
{
    use HasBreadcrumb;

    public $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new PartnerRepository();
        $this->service = new PartnerService();
        $this->breadcrumb_add(route('partner.index'), 'Партнёры');
    }


    public function detail(Request $request, Partner $partner = null)
    {
        if(empty($partner)) abort(404);

        $this->breadcrumb_add('', $partner->name);


        return view('pub.partner.detail', [
            'breadcrumbs' => $this->breadcrumb,
            'partner' => $partner,
        ]);
    }

    /**
     * Страница со списком
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $filter_service = new PartnerListFilterService();
        $table_data = $this->repo->getTable();

        $grades = $types = [];
        foreach(\App\Modules\Pub\Partner\Models\PartnerGrade::cases() as $grade) {
            $row = $grade->data();
            $row['key'] = $grade->value;
            $grades[] = $row;
        }

        $types = [];
        foreach(\App\Modules\Pub\Partner\Models\PartnerType::cases() as $type) {
            $row = $type->data();
            $row['key'] = $type->value;
            $types[] = $row;
        }


        return view('pub.partner.index', [
            'users' => [
                'created_by' => User::whereIn('id', $table_data['filter']['creator'] ?? [])->get(),
            ],
            'breadcrumbs' => $this->breadcrumb,
            'grades' => $grades,
            'types' => $types,
            'filter' => $filter_service->getFilter(),
            'filter_count' => $filter_service->getFilterCount(),
            'user' => []
        ]);
    }


    public function create()
    {
        $this->breadcrumb_add(null, 'Создание');

        $grades = $types = [];
        foreach(\App\Modules\Pub\Partner\Models\PartnerGrade::cases() as $grade) {
            $row = $grade->data();
            $row['key'] = $grade->value;
            $grades[] = $row;
        }

        $types = [];
        foreach(\App\Modules\Pub\Partner\Models\PartnerType::cases() as $type) {
            $row = $type->data();
            $row['key'] = $type->value;
            $types[] = $row;
        }

        return view('pub.partner.create', [
            'breadcrumbs' => $this->breadcrumb,
            'grades' => $grades,
            'types' => $types,
        ]);
    }



    /**
     * Форма редактирования
     *
     * @param \App\Modules\Pub\Partner\Models\Partner $partner
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Partner $partner)
    {
        $this->breadcrumb_add('', 'Редактирование');

        $grades = $types = [];
        foreach(\App\Modules\Pub\Partner\Models\PartnerGrade::cases() as $grade) {
            $row = $grade->data();
            $row['key'] = $grade->value;
            $grades[] = $row;
        }

        $types = [];
        foreach(\App\Modules\Pub\Partner\Models\PartnerType::cases() as $type) {
            $row = $type->data();
            $row['key'] = $type->value;
            $types[] = $row;
        }

        return view('pub.partner.edit', [
            'breadcrumbs' => $this->breadcrumb,
            'grades' => $grades,
            'types' => $types,
            'row' => $partner
        ]);
    }

    /**
     * Обновление
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Modules\Pub\Partner\Models\Partner $partner
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PartnerUpdateRequest $request, Partner $partner)
    {
        //
        $partner->user()->associate($request->input('user'));
        $partner->course()->associate($request->input('course'));
        $partner->fill($request->only($partner->getFillable()))->save();

        return \Redirect::route('partners.detail', $partner);
    }

    /**
     * Удаление
     *
     * @param \App\Modules\Pub\Partner\Models\Partner $partner
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();

        return \Redirect::route('partners.index');
    }

}
