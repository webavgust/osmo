<?php

namespace App\Modules\Pub\DealProject\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\DealProject\Models\DealProject;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerCrmCompany;
use Illuminate\Support\Facades\View;

/**
 * Попапы проектов по сделкам (patch v24).
 *
 * Своей страницы у проектов нет: они живут в реестре сделок и во вкладках
 * карточки партнёра, а редактируются попапами — как привязка КП к сделке.
 */
class DealProjectController extends Controller
{
    /**
     * Попап для сделки: создание проекта либо редактирование её проекта.
     *
     * Если у сделки проект уже есть — открываем его в том же попапе, только
     * с кнопкой «Открепить сделку»: контекст сделки известен.
     *
     * @param CrmDeal $deal
     * @return \Illuminate\Contracts\View\View
     */
    public function box_form(CrmDeal $deal)
    {
        $project = DealProjectService::forDeal($deal);
        $partner = $project?->partner ?? DealProjectService::resolvePartner($deal);

        if (empty($partner)) {
            // партнёра не нашли — предложим выбрать руками, сопоставление
            // компании сделки запомнится в partner_crm_companies (patch v24)
            return View::make('pub.deal_project.boxes.form', [
                'title' => 'Проект по сделке #' . $deal->id,
                'deal' => $deal,
                'project' => null,
                'partner' => null,
                'partners' => $this->partnerOptions(),
            ]);
        }

        $company = $project?->company ?? DealProjectService::resolveCompany($deal, $partner);

        return View::make('pub.deal_project.boxes.form', $this->formData($partner, $company, $project, $deal));
    }

    /**
     * Попап редактирования проекта (без контекста сделки)
     *
     * @param DealProject $project
     * @return \Illuminate\Contracts\View\View
     */
    public function box_edit(DealProject $project)
    {
        $partner = $project->partner;

        if (empty($partner)) {
            return View::make('pub.deal_project.boxes.form', [
                'title' => 'Проект',
                'deal' => null,
                'project' => $project,
                'partner' => null,
                'partners' => collect(),
            ]);
        }

        return View::make('pub.deal_project.boxes.form', $this->formData($partner, $project->company, $project, null));
    }

    /**
     * Попап карточки проекта: сделки, спецификации, комментарий, архив
     *
     * @param DealProject $project
     * @return \Illuminate\Contracts\View\View
     */
    public function box_info(DealProject $project)
    {
        $deals = $project->crmDeals();

        return View::make('pub.deal_project.boxes.info', [
            'title' => $project->label,
            'project' => $project,
            'deals' => $deals,
            // «сделка → КП» уже собрана реестром, второй раз не считаем
            'proposals' => CrmDealRegistryService::proposals(),
            'deal_url' => fn($id) => CrmDealRegistryService::url($id),
            'specifications' => DealProjectService::specifications($project),
        ]);
    }

    /**
     * Партнёры портала для выбора вручную: id, название и пометка о том,
     * сколько компаний Битрикса за ним уже закреплено
     *
     * @return \Illuminate\Support\Collection
     */
    protected function partnerOptions()
    {
        $linked = PartnerCrmCompany::query()
            ->selectRaw('partner_id, COUNT(*) as cnt')
            ->groupBy('partner_id')
            ->pluck('cnt', 'partner_id');

        return Partner::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn(Partner $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'linked' => (int) ($linked[$partner->id] ?? 0),
            ]);
    }

    /**
     * Данные попапа формы: партнёр, его компании, спецификации, проекты
     *
     * @param \App\Modules\Pub\Partner\Models\Partner $partner
     * @param \App\Modules\Pub\Company\Models\Company|null $company
     * @param DealProject|null $project
     * @param CrmDeal|null $deal
     * @return array
     */
    protected function formData($partner, $company, ?DealProject $project, ?CrmDeal $deal): array
    {
        // спецификации показываем все партнёрские: компанию можно сменить
        // прямо в попапе, и список фильтруется на клиенте
        $specs = DealProjectService::availableSpecs($partner);

        $locked = $project
            ? DealProjectService::lockedSpecs($project)
            : DealProjectService::lockedSpecsForDeals($deal ? [$deal->id] : []);

        $checked = $project
            ? DealProjectService::manualSpecIds($project)
            : [];

        return [
            'title' => $project
                ? 'Проект от ' . $project->date_start?->format('d.m.Y')
                : 'Новый проект по сделке #' . $deal?->id,
            'deal' => $deal,
            'project' => $project,
            'partner' => $partner,
            'company' => $company,
            'companies' => $partner->companies,
            'specs' => $specs,
            'locked' => $locked,
            'checked' => $checked,
            // прикрепить к существующему можно только при создании
            'projects' => $project ? collect() : DealProjectService::activeProjects($partner, null, $deal?->id),
        ];
    }
}
