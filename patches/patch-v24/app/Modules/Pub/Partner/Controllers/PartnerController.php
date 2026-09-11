<?php

namespace App\Modules\Pub\Partner\Controllers;

use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
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
use App\Modules\Pub\Partner\Services\PartnerCrmCompanyService;
use App\Modules\Pub\Partner\Services\PartnerListFilterService;
use App\Modules\Pub\Partner\Services\PartnerService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Partner\Requests\PartnerUpdateRequest;
use App\Modules\Pub\User\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class PartnerController extends Controller
{
    use HasBreadcrumb;

    /** Префикс блоков вкладки под каждый режим (patch v24) */
    public const TAB_PREFIX = [
        CrmDealRegistryService::MODE_ALL => 'partner_deal',
        CrmDealRegistryService::MODE_PROJECTS => 'partner_project',
        CrmDealRegistryService::MODE_ARCHIVE => 'partner_archive',
    ];

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
            // сопоставление с Битрикс24 (patch v23)
            'crm_links' => (new PartnerCrmCompanyService())->linked($partner),
        ]);
    }

    /**
     * Вкладки карточки партнёра со сделками (patch v23, режимы — patch v24).
     *
     * Отдаёт кусок разметки — фильтр и таблицу реестра из patch v22,
     * ограниченные компаниями Битрикса этого партнёра. Вкладка грузится
     * ajax'ом и тем же способом перезагружается после смены фильтра,
     * поэтому макет страницы здесь не нужен.
     *
     * Режим (`?mode=`) выбирает вкладку: `all` — «Сделки Битрикс»,
     * `projects` — сделки с действующим проектом, `archive` — с архивным.
     * У каждой вкладки свой префикс, иначе на странице совпали бы id
     * тулбаров, таблиц и модалок фильтра.
     *
     * @param Request $request
     * @param Partner $partner
     * @return \Illuminate\Contracts\View\View
     */
    public function deals(Request $request, Partner $partner)
    {
        $mode = CrmDealRegistryService::mode($request->input('mode'));
        $prefix = static::TAB_PREFIX[$mode];

        // на вкладках проектов отбор «сделки без КП» не имеет смысла
        $defaults = CrmDealRegistryService::modeDefaults($mode);
        $params = CrmDealRegistryService::params($request, $defaults);

        return view('bitrix.deal._tab', array_merge(CrmDealRegistryService::options(), [
            'params' => $params,
            'defaults' => $defaults,
            'rows' => CrmDealRegistryService::rows($params, $partner, $mode),
            'partner' => $partner,
            'action' => route('partner.deals', $partner),
            'prefix' => $prefix,
            'table_id' => $prefix . '_table',
            'ajax' => true,
            'mode' => $mode,
        ]));
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
            // компании Битрикс24 для сопоставления (patch v23)
            'crm_companies' => (new PartnerCrmCompanyService())->options(),
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
            'row' => $partner,
            // компании Битрикс24 для сопоставления (patch v23)
            'crm_companies' => (new PartnerCrmCompanyService())->options($partner),
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
