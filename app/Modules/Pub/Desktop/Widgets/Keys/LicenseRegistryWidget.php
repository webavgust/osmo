<?php

namespace App\Modules\Pub\Desktop\Widgets\Keys;

use App\Modules\Pub\Analytics\Services\LicenseRegistryService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Partner\Models\Partner;

/**
 * Реестр лицензий (patch v30): компактный список ключей со сроками — тот же,
 * что на странице «Реестр лицензий».
 *
 * Отбор — LicenseRegistryService::rows() с теми же параметрами, что у страницы
 * (горизонт, партнёр, только активные, скрыть истёкшие), цвет состояния — state().
 * Сумма строки — сумма спецификации, переведённая в валюту виджета по курсу на сегодня;
 * строки без курса показываются с прочерком и считаются в skipped.
 */
class LicenseRegistryWidget extends Widget
{
    public static function id(): string
    {
        return 'license_registry';
    }

    public static function name(): string
    {
        return 'Реестр лицензий';
    }

    public static function category(): string
    {
        return 'keys';
    }

    public static function description(): string
    {
        return 'Действующие лицензии списком: компания, спецификация, окончание и остаток дней';
    }

    public static function icon(): string
    {
        return 'fa-rectangle-list';
    }

    public static function sizes(): array
    {
        return ['16x8', '32x8', '32x12'];
    }

    public static function defaultSize(): string
    {
        return '16x8';
    }

    public static function order(): int
    {
        return 110;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 600;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'horizon', 'type' => 'select', 'label' => 'Горизонт', 'default' => 'all', 'hint' => 'Горизонты — из константы license_horizons, как на странице реестра',
                'options' => fn() => ['all' => 'Все лицензии']
                    + collect(LicenseRegistryService::horizons())
                        ->mapWithKeys(fn($days) => [(string) $days => 'Истекают за ' . $days . ' дн.'])
                        ->all()
                    + ['expired' => 'Только истёкшие']],
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'options' => fn() => ['all' => 'Все партнёры'] + Partner::orderBy('name')->pluck('name', 'id')->all()],
            ['key' => 'only_active', 'type' => 'bool', 'label' => 'Только активные ключи', 'default' => true],
            ['key' => 'hide_expired', 'type' => 'bool', 'label' => 'Скрыть истёкшие', 'default' => false],
            ['key' => 'show_spec', 'type' => 'bool', 'label' => 'Колонка «Спецификация»', 'default' => true],
            ['key' => 'show_amount', 'type' => 'bool', 'label' => 'Колонка «Сумма»', 'default' => true],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько строк готовить', 'default' => 30, 'min' => 3, 'max' => 200, 'hint' => 'Сколько строк влезет в блок — решает высота'],
        ];
    }

    /**
     * Параметры отбора для LicenseRegistryService::rows() и для ссылки на страницу
     *
     * @param array $settings
     * @return array
     */
    public static function params(array $settings): array
    {
        $horizon = (string) ($settings['horizon'] ?? 'all');
        $partner = (string) ($settings['partner'] ?? 'all');

        return [
            'horizon' => $horizon === 'all' ? null : ($horizon === 'expired' ? 'expired' : (int) $horizon),
            'partner' => $partner === 'all' ? null : (int) $partner,
            'only_active' => !empty($settings['only_active']),
            'hide_expired' => !empty($settings['hide_expired']),
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $params = static::params($settings);

        return route('analytics.licenses', array_filter([
            'horizon' => $params['horizon'],
            'partner' => $params['partner'],
            'only_active' => $params['only_active'] ? 1 : null,
            'hide_expired' => $params['hide_expired'] ? 1 : null,
        ]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 40 лицензий: высокому блоку должно быть чем заполниться; режется по limit, как в data()
        $base = [
            ['ООО «Альфа»', 'ГК Восток', 'Платформа, 50 рабочих мест', 1800000.0],
            ['АО «Вектор»', 'Ташкент-Софт', 'Нейросервисы, годовая', 960000.0],
            ['ООО «Гранит»', 'Луч', 'Платформа, расширение', 2400000.0],
            ['ЗАО «Дельта»', 'Алмаз', 'Пилот, 10 мест', 320000.0],
            ['ООО «Енисей»', 'Норд', 'Платформа, базовая', 1450000.0],
            ['ООО «Магистраль-Логистик»', 'ГК Восток', 'Платформа, 200 рабочих мест', 5200000.0],
            ['АО «Орион»', 'Луч', 'Нейросервисы, квартальная', 240000.0],
            ['ООО «Пульсар»', 'Норд', 'Платформа, отраслевое решение', 3100000.0],
        ];
        $sample = [];
        for ($i = 0; $i < 40; $i++) {
            [$company, $partner, $spec, $amount] = $base[$i % 8];
            $sample[] = [$company . ($i >= 8 ? ' (филиал ' . intdiv($i, 8) . ')' : ''), $partner, $spec, -9 + $i * 11, $amount + $i * 15000];
        }
        $total = count($sample);
        $sum = array_sum(array_column($sample, 4));
        $sample = array_slice($sample, 0, (int) $settings['limit']);

        $rows = array_map(function ($row) {
            $state = LicenseRegistryService::state(LicenseRegistryService::bucket($row[3]));

            return [
                'id' => 0, 'code' => 'XXXX-XXXX', 'company' => $row[0], 'company_url' => null,
                'partner' => $row[1], 'spec' => $row[2], 'contract' => '№ 12/2025',
                'from' => now()->subDays(365 - $row[3])->format('d.m.Y'),
                'to' => now()->addDays($row[3])->format('d.m.Y'),
                'days' => $row[3], 'amount' => $row[4],
                'state_label' => $state['label'], 'state_color' => $state['color'],
            ];
        }, $sample);

        return [
            'rows' => $rows, 'count' => $total, 'companies' => $total,
            'amount' => $sum, 'skipped' => 0,
            'symbol' => '₽', 'label' => 'Все лицензии',
        ];
    }

    /**
     * Строки реестра и итоги
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'code', 'company', 'company_url', 'partner', 'spec', 'contract',
     *     'from', 'to', 'days', 'amount', 'state_label', 'state_color']],
     *     'count', 'companies', 'amount', 'skipped', 'symbol', 'label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $rows = LicenseRegistryService::rows(static::params($settings));
        $totals = LicenseRegistryService::totals($rows);

        // сумма реестра в валюте виджета: по курсу на сегодня, ключи без курса — в skipped
        $amount = 0.0;
        $skipped = 0;
        $converted = [];

        foreach ($rows as $index => $row) {
            $value = CurrencyService::convertAmount((float) $row['spec']['amount'], $row['spec']['currency'], $currency, now());
            $converted[$index] = $value;

            if ($value === null) {
                $skipped++;
                continue;
            }

            $amount += $value;
        }

        $list = $rows->take((int) $settings['limit'])->map(fn($row, $index) => [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'company' => (string) ($row['company']['name'] ?? '—'),
            'company_url' => $row['company']['id'] ? route('company.detail', $row['company']['id']) : null,
            'partner' => (string) ($row['partner']['name'] ?? '—'),
            'spec' => (string) ($row['spec']['name'] ?? '—'),
            'contract' => (string) ($row['contract']['number'] ?? ''),
            'from' => $row['active_from']?->format('d.m.Y'),
            'to' => $row['active_to']?->format('d.m.Y'),
            'days' => $row['days'],
            'amount' => $converted[$index] ?? null,
            'state_label' => $row['state']['label'],
            'state_color' => $row['state']['color'],
        ])->values()->all();

        return [
            'rows' => $list,
            'count' => (int) $totals['count'],
            'companies' => (int) $totals['companies'],
            'amount' => round($amount, 2),
            'skipped' => $skipped,
            'symbol' => $ctx->symbol($currency),
            'label' => static::options(collect(static::fields())->firstWhere('key', 'horizon'))[(string) $settings['horizon']] ?? 'Все лицензии',
        ];
    }
}
