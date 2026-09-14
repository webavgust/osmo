<?php

namespace App\Modules\Pub\Desktop\Widgets\Keys;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\LicenseKey\Services\LicenseRenewalService;
use App\Modules\Pub\Partner\Models\Partner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Продления (patch v30): сколько ключей продлили за период, на какую сумму
 * и как это к предыдущему такому же отрезку.
 *
 * Отдельной сущности «продление» в базе нет, поэтому продлением считается ключ,
 * выданный компании, у которой ключи были и раньше (тот же признак, что и на странице
 * реестра: продление — новый ключ той же компании). Ключ попадает в период по дате
 * начала действия (active_from). Остальные ключи периода — первые у компании, они
 * считаются отдельно как «новые».
 *
 * Сумма — оценка продления LicenseRenewalService::renewalAmount() (сумма спецификации,
 * делённая между её ключами) в валюте виджета по курсу на дату начала ключа;
 * ключи без курса не суммируются (skipped).
 */
class RenewalsWidget extends Widget
{
    public static function id(): string
    {
        return 'renewals';
    }

    public static function name(): string
    {
        return 'Продления';
    }

    public static function category(): string
    {
        return 'keys';
    }

    public static function description(): string
    {
        return 'Сколько ключей продлили за период и на какую сумму, сравнение с прошлым отрезком';
    }

    public static function icon(): string
    {
        return 'fa-rotate-right';
    }

    public static function sizes(): array
    {
        return ['8x4', '8x8', '4x2'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 120;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function usesPeriod(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 900;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'options' => fn() => ['all' => 'Все партнёры'] + Partner::orderBy('name')->pluck('name', 'id')->all()],
            ['key' => 'compare', 'type' => 'bool', 'label' => 'Сравнить с предыдущим отрезком', 'default' => true],
            ['key' => 'show_new', 'type' => 'bool', 'label' => 'Показывать новые ключи', 'default' => true, 'hint' => 'Первый ключ компании — не продление'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $partner = (string) ($settings['partner'] ?? 'all');

        return route('analytics.licenses', array_filter(['partner' => $partner === 'all' ? null : (int) $partner]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 24 продления: высокому блоку должно быть чем заполниться
        $names = ['ООО «Альфа»', 'АО «Вектор»', 'ООО «Гранит»', 'ЗАО «Дельта»', 'ООО «Енисей»', 'ООО «Жемчуг»',
            'АО «Зенит»', 'ООО «Ирбис»', 'ООО «Кедр»', 'АО «Лотос»', 'ООО «Магистраль-Логистик»', 'ООО «Нева»'];
        $rows = [];
        for ($i = 0; $i < 24; $i++) {
            $rows[] = [
                $names[$i % 12] . ($i >= 12 ? ' (филиал)' : ''),
                now()->startOfQuarter()->addDays($i * 3)->format('d.m.Y'),
                320000.0 + (($i * 7) % 12) * 180000.0,
            ];
        }

        return [
            'count' => 26, 'amount' => 21400000.0, 'skipped' => 0,
            'new_count' => 4, 'new_amount' => 2100000.0,
            'prev_count' => 20, 'prev_amount' => 17900000.0, 'delta_percent' => 30.0,
            'label' => 'Квартал', 'dates' => '01.07.2026 – 30.09.2026', 'symbol' => '₽',
            'rows' => array_map(fn($row) => ['company' => $row[0], 'date' => $row[1], 'amount' => $row[2], 'url' => null], $rows),
        ];
    }

    /**
     * Продления за период и сравнение с предыдущим отрезком
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['count', 'amount', 'skipped', 'new_count', 'new_amount', 'prev_count',
     *     'prev_amount', 'delta_percent', 'label', 'dates', 'symbol',
     *     'rows' => [['company', 'date', 'amount', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        $partner = (string) $settings['partner'] === 'all' ? null : (int) $settings['partner'];

        [$prev_from, $prev_to] = DesktopContext::previousRange($period['key']);

        $current = static::totals($period['from'], $period['to'], $currency, $partner);
        $previous = $settings['compare'] ? static::totals($prev_from, $prev_to, $currency, $partner) : null;

        return [
            'count' => $current['count'],
            'amount' => $current['amount'],
            'skipped' => $current['skipped'],
            'new_count' => $current['new_count'],
            'new_amount' => $current['new_amount'],
            'prev_count' => $previous['count'] ?? null,
            'prev_amount' => $previous['amount'] ?? null,
            'delta_percent' => !empty($previous['count'])
                ? round(($current['count'] - $previous['count']) / abs($previous['count']) * 100, 1)
                : null,
            'label' => $period['label'],
            'dates' => $period['dates'],
            'symbol' => $ctx->symbol($currency),
            'rows' => $current['rows'],
        ];
    }

    /**
     * Ключи, начавшие действовать в отрезке: продления (у компании ключи уже были)
     * и новые, с оценкой суммы в валюте виджета
     *
     * @param Carbon $from
     * @param Carbon $to
     * @param string $currency
     * @param int|null $partner
     * @return array ['count', 'amount', 'new_count', 'new_amount', 'skipped', 'rows']
     */
    public static function totals(Carbon $from, Carbon $to, string $currency, ?int $partner = null): array
    {
        $keys = LicenseKey::query()
            ->with(['company', 'specification'])
            ->whereBetween('active_from', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->when($partner, fn($query) => $query->whereHas('specification.contract', fn($builder) => $builder->where('partner_id', $partner)))
            ->orderByDesc('active_from')
            ->get();

        // первая дата ключа у каждой компании: ключ позже неё — продление
        $first = DB::table('license_keys')
            ->groupBy('company_id')
            ->selectRaw('company_id, MIN(active_from) as first_from')
            ->pluck('first_from', 'company_id');

        $ret = ['count' => 0, 'amount' => 0.0, 'new_count' => 0, 'new_amount' => 0.0, 'skipped' => 0, 'rows' => []];

        foreach ($keys as $key) {
            $start = $key->active_from;
            $earliest = $first[$key->company_id] ?? null;
            $renewal = $start !== null && $earliest !== null && Carbon::parse($earliest)->startOfDay()->lt($start->startOfDay());

            $amount = CurrencyService::convertAmount(
                LicenseRenewalService::renewalAmount($key),
                $key->specification?->currency_slug,
                $currency,
                $start ?? now()
            );

            if ($amount === null) {
                $ret['skipped']++;
            }

            if ($renewal) {
                $ret['count']++;
                $ret['amount'] += (float) $amount;

                // до 40 строк: высокий блок показывает их списком, лишние прячет .desk-fit
                if (count($ret['rows']) < 40) {
                    $ret['rows'][] = [
                        'company' => (string) ($key->company?->name ?? '—'),
                        'date' => $start?->format('d.m.Y'),
                        'amount' => $amount,
                        'url' => $key->company_id ? route('company.detail', $key->company_id) : null,
                    ];
                }

                continue;
            }

            $ret['new_count']++;
            $ret['new_amount'] += (float) $amount;
        }

        $ret['amount'] = round($ret['amount'], 2);
        $ret['new_amount'] = round($ret['new_amount'], 2);

        return $ret;
    }
}
