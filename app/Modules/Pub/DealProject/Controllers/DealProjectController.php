<?php

namespace App\Modules\Pub\DealProject\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Company\Models\Company;
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
            // «сделка → все её КП» (patch v43) уже собрана реестром, второй раз не считаем
            'proposals' => CrmDealRegistryService::proposals(),
            'deal_url' => fn($id) => CrmDealRegistryService::url($id),
            'specifications' => DealProjectService::specifications($project),
        ]);
    }

    /**
     * Попап «Новый проект» со страницы компании (patch v48).
     *
     * Шаблон pub.deal_project.boxes.company_form получает:
     *  - string $title — заголовок попапа;
     *  - Company $company — компания проекта (фиксирована);
     *  - Partner|null $partner — партнёр компании; null — проект завести нельзя,
     *    шаблон показывает предупреждение вместо формы;
     *  - Collection $specs — спецификации компании (ContractSpecification
     *    с contract, company, currency), свежие сверху;
     *  - Collection $busy — id спецификации → DealProject: спецификации
     *    в действующих проектах, их выводить неактивными с подписью проекта;
     *  - Collection $spec_deals — id свободной спецификации → int[] сделок
     *    Битрикса, которые прикрепятся по цепочке спецификация → КП → сделка;
     *  - Collection $deal_projects — id сделки → DealProject: сделки из
     *    $spec_deals, уже прикреплённые к другим проектам (будут пропущены);
     *  - Collection $projects — проекты компании (DealProjectService::forCompany),
     *    действующие сверху — для справки о параллельных проектах;
     *  - string $action — URL отправки формы (api.deal_project.store_company).
     * Поля формы: date_start, is_pilot, deadline (только пилоту), comment, specs[].
     *
     * @param Company $company
     * @return \Illuminate\Contracts\View\View
     */
    public function box_company(Company $company)
    {
        $partner = $company->partner;

        $specs = $partner ? DealProjectService::availableSpecs($partner, $company) : collect();

        $busy = DealProjectService::specProjectMap($company)
            ->filter(fn(DealProject $project) => !$project->is_archived);

        $spec_deals = $specs
            ->reject(fn($spec) => $busy->has($spec->id))
            ->mapWithKeys(fn($spec) => [(int) $spec->id => DealProjectService::dealIdsForSpecs([$spec->id])])
            ->filter();

        $deal_projects = DealProjectService::forDeals($spec_deals->flatten()->unique()->all());

        return View::make('pub.deal_project.boxes.company_form', [
            'title' => 'Новый проект · ' . $company->name,
            'company' => $company,
            'partner' => $partner,
            'specs' => $specs,
            'busy' => $busy,
            'spec_deals' => $spec_deals,
            'deal_projects' => $deal_projects,
            'projects' => DealProjectService::forCompany($company),
            'action' => route('api.deal_project.store_company', $company),
        ]);
    }

    /**
     * Попап закрытия проекта (patch v48).
     *
     * Шаблон pub.deal_project.boxes.close получает:
     *  - string $title — заголовок попапа;
     *  - DealProject $project — закрываемый проект (если уже в архиве —
     *    меняется только итог);
     *  - array $results — итоги [success => 'Успешно', fail => 'Неуспешно'],
     *    выбор обязателен (поле result);
     *  - Collection $hints — строки DealProjectService::closeHints(): spec, id, name,
     *    from_proposal, currency, amount, paid, rest, status, status_label,
     *    status_color, suggest_close. Чекбокс close_specs[] по умолчанию
     *    отмечен только при suggest_close; закрытые/отменённые — без чекбокса;
     *  - Collection $deals — сделки Битрикса проекта (CrmDeal);
     *  - string $action — URL отправки формы (api.deal_project.close).
     * Поля формы: result, result_comment, close_specs[].
     *
     * @param DealProject $project
     * @return \Illuminate\Contracts\View\View
     */
    public function box_close(DealProject $project)
    {
        return View::make('pub.deal_project.boxes.close', [
            'title' => 'Закрыть проект · ' . $project->label,
            'project' => $project,
            'results' => DealProject::RESULTS,
            'hints' => DealProjectService::closeHints($project),
            'deals' => $project->crmDeals(),
            'action' => route('api.deal_project.close', $project),
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
