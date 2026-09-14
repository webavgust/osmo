<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\ExternalProposal\Models\ExternalProposal;
use App\Modules\Pub\ExternalProposal\Services\ExternalProposalService;
use App\Modules\Pub\ExternalProposal\Services\OsmoviewCpClient;

/**
 * Внешние КП (patch v30): сводка по КП OSMOVIEW CP — сколько ещё не перенесено
 * в портал, сколько появилось за последние дни, и список последних записей.
 *
 * Строки и отбор — ExternalProposalService::rows() (тот же сервис, что у страницы
 * «Внешние КП»), поля списка — как в её таблице: номер, название, заказчик, камеры,
 * дата, валюта и тип лицензий. Сумм у внешних КП на странице нет, поэтому нет и здесь.
 * «Новые» считаются по дате документа во внешней системе (created_at_remote).
 */
class ExternalProposalsWidget extends Widget
{
    /** За сколько дней запись считается новой, по умолчанию */
    public const FRESH_DAYS = 7;

    public static function id(): string { return 'external_proposals'; }

    public static function name(): string { return 'Внешние КП'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'КП OSMOVIEW CP: сколько не перенесено в портал, сколько новых и список последних';
    }

    public static function icon(): string { return 'fa-cloud-arrow-down'; }

    public static function sizes(): array { return ['8x4', '4x2', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 200; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'only_pending', 'type' => 'bool', 'label' => 'Только не перенесённые', 'default' => true],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько записей', 'default' => 10, 'min' => 3, 'max' => 50],
            ['key' => 'fresh_days', 'type' => 'number', 'label' => 'Новые за, дней', 'default' => self::FRESH_DAYS, 'min' => 1, 'max' => 90,
                'hint' => 'Считается по дате КП во внешней системе'],
            ['key' => 'show_license', 'type' => 'bool', 'label' => 'Валюта и тип лицензий', 'default' => true,
                'hint' => 'Показываются только у записей с загруженным detail'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('external_proposal.index', empty($settings['only_pending']) ? [] : ['transferred' => 'no']);
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
        $sample = [
            ['Метровагонмаш: 150 камер', 'Метровагонмаш', 150, 'RUB', 'year'],
            ['Project 70 cameras inactivity detection', 'Ozon', 70, 'USD', 'unlimited'],
            ['Пилот на проходной', 'ООО «Вектор»', 24, 'RUB', 'mixed'],
            ['Контроль периметра', 'ООО «Гранит»', 48, 'RUB', 'year'],
            ['Склад: распознавание номеров', 'ООО «Дельта»', 12, 'EUR', 'unlimited'],
        ];

        $rows = [];

        // 42 записи, каждая пятая перенесена: хватает, чтобы заполнить высокий блок
        for ($i = 0; $i < 42; $i++) {
            [$name, $customer, $cameras, $currency, $license] = $sample[$i % 5];
            $number = 'AK' . (747 - $i * 3);
            $days = 1 + $i * 2;
            $transferred = $i % 5 === 4;
            $license_info = ExternalProposal::LICENSE_TYPES[$license] ?? null;

            $rows[] = [
                'id' => $i + 1,
                'number' => $number,
                'name' => $name,
                'customer' => $customer,
                'cameras' => $cameras,
                'date' => now()->subDays($days)->format('d.m.Y'),
                'days' => $days,
                'fresh' => $days <= (int) $settings['fresh_days'],
                'currency' => $currency,
                'license' => (string) ($license_info['label'] ?? ''),
                'license_color' => (string) ($license_info['color'] ?? 'secondary'),
                'transferred' => $transferred,
                'proposal_url' => null,
                'detail_url' => null,
                'transfer_url' => null,
            ];
        }

        $fresh = count(array_filter($rows, fn($row) => $row['fresh']));

        if (!empty($settings['only_pending'])) {
            $rows = array_values(array_filter($rows, fn($row) => !$row['transferred']));
        }

        // как в data(): записей не больше, чем задано в настройке
        $rows = array_slice($rows, 0, max(1, (int) $settings['limit']));

        return [
            'pending' => 34, 'transferred' => 8, 'total' => 42, 'fresh' => $fresh,
            'without_payload' => 0, 'fresh_days' => (int) $settings['fresh_days'],
            'rows' => $rows, 'configured' => true,
        ];
    }

    /**
     * Сводка и последние записи внешней системы
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['pending', 'transferred', 'total', 'fresh', 'without_payload', 'fresh_days',
     *     'rows' => [['id', 'number', 'name', 'customer', 'cameras', 'date', 'days', 'fresh',
     *     'currency', 'license', 'license_color', 'transferred', 'proposal_url', 'detail_url',
     *     'transfer_url']], 'configured']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $fresh_days = max(1, (int) $settings['fresh_days']);
        $edge = now()->subDays($fresh_days)->startOfDay();

        $all = ExternalProposal::source();

        $counters = [
            'total' => (clone $all)->count(),
            'transferred' => (clone $all)->transferred(true)->count(),
            'pending' => (clone $all)->transferred(false)->count(),
            'without_payload' => (clone $all)->whereNull('payload')->count(),
            'fresh' => (clone $all)->where('created_at_remote', '>=', $edge->format('Y-m-d H:i:s'))->count(),
            'fresh_days' => $fresh_days,
            // тот же признак, что на странице: без ключа API синхронизация невозможна
            'configured' => (new OsmoviewCpClient())->configured(),
        ];

        $rows = (new ExternalProposalService())
            ->rows(['transferred' => empty($settings['only_pending']) ? 'all' : 'no'])
            ->take(max(1, (int) $settings['limit']));

        return $counters + ['rows' => $this->pack($rows, $edge)];
    }

    /**
     * Строки списка: те же поля, что в таблице страницы «Внешние КП»
     *
     * @param \Illuminate\Support\Collection $rows записи внешней системы
     * @param \Carbon\Carbon $edge граница «новых»
     * @return array
     */
    protected function pack($rows, $edge): array
    {
        return $rows->map(function (ExternalProposal $row) use ($edge) {
            $license = ExternalProposal::LICENSE_TYPES[(string) $row->license_type] ?? null;
            $date = $row->created_at_remote;

            return [
                'id' => (int) $row->id,
                'number' => (string) ($row->external_number ?: $row->external_id),
                'name' => (string) $row->name,
                'customer' => (string) ($row->customer ?? ''),
                'cameras' => $row->cameras === null ? null : (int) $row->cameras,
                'date' => $date?->format('d.m.Y'),
                'days' => $date ? (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null,
                'fresh' => $date !== null && $date->gte($edge),
                // валюта и тип лицензий живут в payload — у записей без detail их нет
                'currency' => $row->has_payload ? (string) $row->currency : '',
                'license' => (string) ($license['label'] ?? ''),
                'license_color' => (string) ($license['color'] ?? 'secondary'),
                'transferred' => !empty($row->proposal_group),
                'proposal_url' => $row->proposal
                    ? route('proposal.detail', [$row->proposal->group, $row->proposal->iteration])
                    : null,
                'detail_url' => route('external_proposal.box_detail', $row->id),
                'transfer_url' => route('external_proposal.box_transfer', $row->id),
            ];
        })->values()->all();
    }
}
