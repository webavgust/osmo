<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use Illuminate\Support\Facades\Route;

/**
 * Карточка сделки (patch v30): выбранная в настройках сделка Битрикс24 —
 * живые название, стадия и сумма из зеркала crm_deal + crm_deal_uf.
 *
 * Клик по названию — сводная карточка сделки на портале (она строится по КП,
 * привязанному к сделке, и требует права deal_card_view); «↗» — сама сделка
 * в Битрикс24. Проект по сделке открывается попапом, как в реестре сделок.
 *
 * Сумма сделки лежит в своей валюте, поэтому под валюту стола пересчитывается
 * курсом на сегодня; курса нет — показываем сумму как есть.
 */
class DealCardWidget extends Widget
{
    /** Римские номера кварталов (подпись планового квартала) */
    public const QUARTERS = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];

    public static function id(): string { return 'deal_card'; }

    public static function name(): string { return 'Карточка сделки'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Выбранная сделка Битрикс24: стадия, сумма, ответственный и ссылки';
    }

    public static function icon(): string { return 'fa-handshake'; }

    public static function sizes(): array { return ['8x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 400; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'entity', 'label' => 'Сделка Битрикс24', 'entities' => ['deal'], 'required' => true, 'default' => null,
                'hint' => 'Поиск по названию или id сделки'],
            ['key' => 'stage', 'type' => 'bool', 'label' => 'Стадия', 'default' => true],
            ['key' => 'money', 'type' => 'bool', 'label' => 'Сумма', 'default' => true],
            ['key' => 'parties', 'type' => 'bool', 'label' => 'Партнёр и заказчик', 'default' => true],
            ['key' => 'quarter', 'type' => 'bool', 'label' => 'Квартал плана', 'default' => true],
            ['key' => 'links', 'type' => 'bool', 'label' => 'КП и проект', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('crm-deal.index', ['has_proposal' => 'all']);
    }

    /**
     * Id выбранной сделки (0 — не выбрана)
     *
     * @param array $settings
     * @return int
     */
    public static function dealId(array $settings): int
    {
        $id = (string) ($settings['target']['id'] ?? '');

        return (string) ($settings['target']['type'] ?? '') === 'deal' && ctype_digit($id) ? (int) $id : 0;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return [
            'found' => true,
            'id' => 1042,
            'title' => 'Платформа Восток — пилот',
            'stage' => 'TCP',
            'amount' => 12400000.0,
            'symbol' => '₽',
            'converted' => true,
            'manager' => 'Анна Август.',
            'partner' => 'ООО «Интегратор»',
            'customer' => 'АО «Восток»',
            'quarter' => 'IV квартал 2026',
            'proposal' => 'КП № AA-794 · Платформа Восток',
            'proposal_url' => null,
            'project' => 'Проект от 12.03.2026',
            'project_url' => null,
            'url' => null,
            'bitrix_url' => null,
        ];
    }

    /**
     * Сделка Битрикс24 для карточки
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['found', 'id', 'title', 'stage', 'amount', 'symbol', 'converted', 'manager', 'partner', 'customer', 'quarter', 'proposal', 'proposal_url', 'project', 'project_url', 'url', 'bitrix_url']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $id = static::dealId($settings);

        $out = [
            'found' => false, 'id' => $id, 'title' => '', 'stage' => '', 'amount' => null,
            'symbol' => $ctx->symbol($currency), 'converted' => true, 'manager' => '', 'partner' => '', 'customer' => '',
            'quarter' => '', 'proposal' => '', 'proposal_url' => null, 'project' => '', 'project_url' => null,
            'url' => null, 'bitrix_url' => null,
        ];

        if ($id <= 0) {
            return $out;
        }

        $deal = CrmDeal::with('dealUf')->find($id);
        if (!$deal) {
            return $out;
        }

        // сумма сделки хранится в своей валюте — приводим к валюте виджета по сегодняшнему курсу
        $from = (string) ($deal->currency_id ?: $currency);
        $amount = (float) $deal->opportunity;
        $converted = $from === $currency ? $amount : CurrencyService::convertAmount($amount, $from, $currency);

        $out['found'] = true;
        $out['title'] = (string) $deal->title;
        $out['stage'] = (string) $deal->stage_name;
        $out['amount'] = $converted === null ? $amount : (float) $converted;
        $out['converted'] = $converted !== null;
        // пересчитать не удалось — показываем сумму в валюте самой сделки
        $out['symbol'] = $ctx->symbol($converted === null ? $from : $currency);
        $out['manager'] = (string) $deal->manager;
        $out['partner'] = (string) $deal->company_name;
        $out['customer'] = trim((string) ($deal->dealUf?->{CrmDealRegistryService::ufCustomer()} ?? ''));
        $out['quarter'] = static::quarterLabel((string) ($deal->dealUf?->{DealProjectService::ufQuarter()} ?? ''));
        $out['bitrix_url'] = CrmDealRegistryService::url($id);

        return array_merge($out, static::linksOf($id, $ctx));
    }

    /**
     * Привязанное КП, сводная карточка портала и проект по сделке
     *
     * @param int $id
     * @param DesktopContext $ctx
     * @return array ['proposal', 'proposal_url', 'project', 'project_url', 'url']
     */
    protected static function linksOf(int $id, DesktopContext $ctx): array
    {
        $out = ['proposal' => '', 'proposal_url' => null, 'project' => '', 'project_url' => null, 'url' => null];

        // КП сделки: сначала привязки patch v24, потом старое поле КП
        $group = ProposalCrmDeal::where('crm_deal_id', $id)->orderByDesc('is_main')->value('proposal_group')
            ?? Proposal::where('crm_deal_id', $id)->value('group');

        if ($group) {
            $proposal = Proposal::where('group', $group)->orderByDesc('iteration')->first();

            if ($proposal) {
                $number = trim((string) $proposal->number) !== '' ? trim((string) $proposal->number) : 'б/н';
                $out['proposal'] = '№ ' . $number . ' · ' . $proposal->name;
                $out['proposal_url'] = route('proposal.detail', [$proposal, $proposal->iteration]);

                // сводная карточка сделки на портале строится по КП и закрыта правом
                if ($ctx->user && Route::has('deal_card.index') && $ctx->user->can_do('deal_card_view')) {
                    $out['url'] = route('deal_card.index', $group);
                }
            }
        }

        $project = DealProjectService::forDeal($id);
        if ($project) {
            $out['project'] = $project->label . ($project->is_archived ? ' (архив)' : '');
            $out['project_url'] = Route::has('deal_project.box_info') ? route('deal_project.box_info', $project) : null;
        }

        return $out;
    }

    /**
     * Подпись планового квартала: «2026q4» → «IV квартал 2026», «не выбрано» — пусто
     *
     * @param string $quarter
     * @return string
     */
    public static function quarterLabel(string $quarter): string
    {
        return preg_match('/^(\d{4})q([1-4])$/', trim($quarter), $match)
            ? static::QUARTERS[(int) $match[2]] . ' квартал ' . $match[1]
            : '';
    }
}
