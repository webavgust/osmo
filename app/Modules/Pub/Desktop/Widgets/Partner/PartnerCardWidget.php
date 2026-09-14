<?php

namespace App\Modules\Pub\Desktop\Widgets\Partner;

use App\Modules\Pub\Analytics\Services\PartnerScoringService;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;

/**
 * Карточка партнёра (patch v30): выбранный партнёр с ключевыми цифрами за год.
 *
 * Грейд и название — из карточки партнёра, балл, место и суммы — из
 * PartnerScoringService::ranked() (та же выборка, что на странице скоринга).
 * Суммы скоринга приведены к рублю, поэтому под валюту стола пересчитываются
 * курсом на сегодня; курса нет — показываем рубли.
 */
class PartnerCardWidget extends Widget
{
    public static function id(): string { return 'partner_card'; }

    public static function name(): string { return 'Карточка партнёра'; }

    public static function category(): string { return 'partner'; }

    public static function description(): string
    {
        return 'Мини-карточка партнёра: грейд, балл скоринга и ключевые суммы за год';
    }

    public static function icon(): string { return 'fa-address-card'; }

    public static function sizes(): array { return ['8x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 110; }

    public static function ttl(): int { return 900; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'entity', 'label' => 'Партнёр', 'entities' => ['partner'], 'required' => true, 'default' => null, 'hint' => 'Поиск по названию партнёра'],
            ['key' => 'year', 'type' => 'select', 'label' => 'Год', 'default' => 'auto', 'hint' => '«Текущий» — последний год с данными, как на странице скоринга',
                'options' => fn() => ['auto' => 'Текущий'] + collect(PartnerScoringService::years())
                    ->mapWithKeys(fn($year) => [(string) $year => (string) $year])
                    ->all()],
            ['key' => 'scoring', 'type' => 'bool', 'label' => 'Балл и место в рейтинге', 'default' => true],
            ['key' => 'money', 'type' => 'bool', 'label' => 'Ключевые суммы', 'default' => true, 'hint' => 'Подписано, оплачено, ожидаем'],
            ['key' => 'counts', 'type' => 'bool', 'label' => 'Компании, договоры, сделки', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $id = static::partnerId($settings);

        return $id > 0 ? route('partner.detail', $id) : route('partner.index');
    }

    /**
     * Id выбранного партнёра (0 — не выбран)
     *
     * @param array $settings
     * @return int
     */
    public static function partnerId(array $settings): int
    {
        return (string) ($settings['target']['type'] ?? '') === 'partner'
            ? (int) ($settings['target']['id'] ?? 0)
            : 0;
    }

    /**
     * Год карточки: «Текущий» — последний год с данными (как на странице скоринга)
     *
     * @param array $settings
     * @return int|null
     */
    public static function year(array $settings): ?int
    {
        $year = (string) ($settings['year'] ?? 'auto');
        if ($year !== 'auto') return (int) $year;

        $years = PartnerScoringService::years();

        return empty($years) ? null : (int) $years[0];
    }

    /**
     * Образцовые данные для превью библиотеки (без запросов к базе)
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $grade = PartnerGrade::GOLD->data();
        $rank = PartnerScoringService::rank(81);

        return [
            'found' => true, 'scored' => true, 'name' => 'ГК Восток', 'url' => null, 'active' => true,
            'region' => 'Ташкент', 'grade' => $grade['label'], 'grade_hint' => $grade['description'],
            'grade_color' => $grade['color']['medal'], 'year' => (int) now()->year,
            'symbol' => $ctx->symbol($ctx->currencyFor($settings)),
            'score' => 81, 'place' => 2, 'total' => 48,
            'rank_letter' => $rank['letter'], 'rank_label' => $rank['label'], 'rank_color' => $rank['color'],
            'companies' => 12, 'contracts' => 5, 'specs_signed' => 9, 'specs_sum' => 41500000.0,
            'paid_sum' => 33800000.0, 'expected_sum' => 7600000.0, 'overdue_sum' => 0.0,
            'amount_won' => 46200000.0, 'proposals' => 21, 'won' => 14, 'conversion' => 73.6,
            'deals' => 18, 'deals_sum' => 52000000.0, 'projects' => 6, 'crm_linked' => true,
        ];
    }

    /**
     * Карточка выбранного партнёра
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['found', 'scored', 'name', 'url', 'active', 'region', 'grade', 'grade_hint',
     *     'grade_color', 'year', 'symbol', 'score', 'place', 'total', 'rank_letter', 'rank_label',
     *     'rank_color', 'companies', 'contracts', 'specs_signed', 'specs_sum', 'paid_sum',
     *     'expected_sum', 'overdue_sum', 'amount_won', 'proposals', 'won', 'conversion',
     *     'deals', 'deals_sum', 'projects', 'crm_linked']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $empty = [
            'found' => false, 'scored' => false, 'name' => '', 'url' => null, 'active' => true,
            'region' => '', 'grade' => '', 'grade_hint' => '', 'grade_color' => '',
            'year' => static::year($settings), 'symbol' => $ctx->symbol($ctx->currencyFor($settings)),
            'score' => null, 'place' => null, 'total' => 0,
            'rank_letter' => '', 'rank_label' => '', 'rank_color' => 'secondary',
            'companies' => 0, 'contracts' => 0, 'specs_signed' => 0, 'specs_sum' => 0.0,
            'paid_sum' => 0.0, 'expected_sum' => 0.0, 'overdue_sum' => 0.0, 'amount_won' => 0.0,
            'proposals' => 0, 'won' => 0, 'conversion' => null,
            'deals' => 0, 'deals_sum' => 0.0, 'projects' => 0, 'crm_linked' => false,
        ];

        $id = static::partnerId($settings);
        if ($id <= 0) return $empty;

        $partner = Partner::withCount(['companies', 'contracts'])->find($id);
        if ($partner === null) return $empty;

        $year = static::year($settings);
        $ranked = PartnerScoringService::ranked($year);
        $row = $ranked->first(fn($item) => (int) $item['partner']->id === $id);

        // суммы скоринга посчитаны в рублях; курса к валюте стола нет — оставляем рубли
        $currency = $ctx->currencyFor($settings);
        $rate = $currency === Currency::CURRENCY_DEFAULT
            ? 1.0
            : (float) (CurrencyService::getConvertRateForDate(now(), Currency::CURRENCY_DEFAULT, $currency) ?? 0);

        if ($rate <= 0) {
            [$currency, $rate] = [Currency::CURRENCY_DEFAULT, 1.0];
        }

        $grade = PartnerGrade::tryFrom((string) $partner->grade)?->data();
        $money = fn($value) => (float) $value * $rate;

        $card = [
            'found' => true,
            'scored' => $row !== null,
            'name' => (string) $partner->name,
            'url' => route('partner.detail', $partner->id),
            'active' => (bool) $partner->active,
            'region' => (string) ($partner->region ?? ''),
            'grade' => (string) ($grade['label'] ?? ''),
            'grade_hint' => (string) ($grade['description'] ?? ''),
            'grade_color' => (string) ($grade['color']['medal'] ?? ''),
            'year' => $year,
            'symbol' => $ctx->symbol($currency),
            'total' => $ranked->count(),
            'companies' => (int) $partner->companies_count,
            'contracts' => (int) $partner->contracts_count,
        ];

        if ($row === null) {
            return array_merge($empty, $card);
        }

        return array_merge($empty, $card, [
            'score' => (int) round($row['score']),
            'place' => (int) $row['place'],
            'rank_letter' => (string) $row['rank']['letter'],
            'rank_label' => (string) $row['rank']['label'],
            'rank_color' => (string) $row['rank']['color'],
            'specs_signed' => (int) $row['specs_signed'],
            'specs_sum' => $money($row['specs_sum']),
            'paid_sum' => $money($row['paid_sum']),
            'expected_sum' => $money($row['expected_sum']),
            'overdue_sum' => $money($row['overdue_sum']),
            'amount_won' => $money($row['amount_won']),
            'proposals' => (int) $row['proposals'],
            'won' => (int) $row['won'],
            'conversion' => $row['conversion'] === null ? null : round((float) $row['conversion'], 1),
            'deals' => (int) $row['deals'],
            'deals_sum' => $money($row['deals_sum']),
            'projects' => (int) $row['projects'],
            'crm_linked' => (bool) $row['crm_linked'],
        ]);
    }
}
