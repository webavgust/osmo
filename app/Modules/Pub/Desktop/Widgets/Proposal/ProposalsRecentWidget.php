<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Illuminate\Support\Collection;

/**
 * Последние КП (patch v30): недавно изменённые КП — номер, компания, статус,
 * сумма основного варианта и ссылка на карточку.
 *
 * Строки — последние редакции групп (ProposalStatusService::latestIterations()),
 * порядок по updated_at. Сумма берётся у основного варианта (variants: is_main desc, id)
 * и показывается в валюте самого КП — ровно как в колонке «Стоимость» списка КП,
 * без пересчёта курса. Статусы — три из v27.
 */
class ProposalsRecentWidget extends Widget
{
    /** Сколько дней редакция считается свежей */
    public const FRESH_DAYS = 7;

    public static function id(): string { return 'proposals_recent'; }

    public static function name(): string { return 'Последние КП'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Недавно изменённые КП: номер, компания, статус и сумма основного варианта';
    }

    public static function icon(): string { return 'fa-clock-rotate-left'; }

    public static function sizes(): array { return ['8x8', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 120; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько КП', 'default' => 10, 'min' => 3, 'max' => 50],
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои (менеджер — я)', 'default' => false],
            ['key' => 'status', 'type' => 'select', 'label' => 'Статус', 'default' => 'all', 'options' => fn() => static::statusOptions()],
            ['key' => 'show_cost', 'type' => 'bool', 'label' => 'Показывать сумму и валюту', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return ['rows' => static::sampleRows(), 'total' => 128];
    }

    /**
     * Недавно изменённые КП
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [...], 'total']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $rows = ProposalStatusService::latestIterations();
        $rows = static::applyStatus($rows, (string) $settings['status']);

        if (!empty($settings['mine'])) {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $rows = $rows->filter(fn($row) => (int) $row->manager_id === $user_id);
        }

        $total = $rows->count();
        $take = $rows->sortByDesc(fn($row) => $row->updated_at ?? $row->created_at)
            ->take((int) $settings['limit'])
            ->values();

        return ['rows' => static::pack($take), 'total' => $total];
    }

    /**
     * Варианты статусов для настройки
     *
     * @return array
     */
    public static function statusOptions(): array
    {
        $options = ['all' => 'Все'];

        foreach (ProposalStatus::cases() as $case) {
            $options[$case->value] = $case->data()['label'];
        }

        return $options;
    }

    /**
     * Отбор по статусу; пустой статус считается «В работе», как в ProposalStatusService::counters()
     *
     * @param Collection $rows
     * @param string $status код статуса или 'all'
     * @return Collection
     */
    public static function applyStatus(Collection $rows, string $status): Collection
    {
        if ($status === 'all' || !ProposalStatus::tryFrom($status)) return $rows->values();

        return $rows->filter(fn($row) => ($row->status ?? ProposalStatus::IN_WORK->value) === $status)->values();
    }

    /**
     * Строки списка для вьюхи. Общая упаковка для всех трёх списочных виджетов КП
     * («Последние КП», «Мои КП», «КП без сделки»)
     *
     * @param Collection $proposals редакции КП
     * @return array [['group', 'number', 'name', 'company', 'partner', 'manager', 'status', 'status_label',
     *     'status_color', 'status_icon', 'amount', 'symbol', 'date', 'updated', 'days', 'fresh',
     *     'url', 'status_url', 'deal_url']]
     */
    public static function pack(Collection $proposals): array
    {
        if ($proposals->isEmpty()) return [];

        // связи подгружаем одним заходом: список короткий, но без этого будет N+1
        $proposals->load(['company', 'partner', 'variants', 'manager']);

        $currencies = DesktopContext::currencies();

        return $proposals->map(function (Proposal $row) use ($currencies) {
            $status = $row->status_enum;
            $info = $status->data();
            // secondary в Metronic почти не виден — как в виджете «КП по статусам», показываем dark
            $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];

            $variant = $row->variants->first();
            $updated = $row->updated_at ?? $row->created_at;

            return [
                'group' => (string) $row->group,
                'number' => trim((string) $row->number),
                'name' => (string) $row->name,
                'company' => (string) ($row->company?->name ?? ''),
                'partner' => (string) ($row->partner?->name ?? ''),
                'manager' => (string) ($row->manager?->full_name ?? $row->manager?->name ?? ''),
                'status' => $status->value,
                'status_label' => $info['label'],
                'status_color' => $color,
                'status_icon' => $info['icon'],
                'amount' => $variant?->cost_total !== null ? (float) $variant->cost_total : null,
                'symbol' => (string) ($currencies[(string) $row->currency_slug]->symbol ?? $row->currency_slug),
                'date' => $row->sended_at?->format('d.m.Y'),
                'updated' => $updated?->format('d.m.Y'),
                'days' => $row->sended_at ? (int) $row->sended_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null,
                'fresh' => $updated !== null && $updated->gte(now()->subDays(static::FRESH_DAYS)),
                'url' => route('proposal.detail', [$row->group, $row->iteration]),
                'status_url' => route('proposal.box_status', [$row->group, $row->iteration]),
                'deal_url' => route('proposal.box_deal', [$row->group, $row->iteration]),
            ];
        })->all();
    }

    /**
     * Образцовые строки для превью библиотеки — общие для списочных виджетов КП
     *
     * @param string|null $status принудительный статус
     * @return array
     */
    public static function sampleRows(?string $status = null): array
    {
        $sample = [
            ['1024', 'Видеоаналитика на складе', 'ООО «Альфа»', 'ГК Восток', 'won', 4800000, 3, 12],
            ['1023', 'Пилот на проходной', 'АО «Вектор»', 'Ташкент-Софт', 'in_work', 1250000, 6, 21],
            ['1021', 'Контроль периметра', 'ООО «Гранит»', 'Луч', 'in_work', 3100000, 9, 34],
            ['1018', 'Распознавание номеров', 'ООО «Дельта»', 'Алмаз', 'lost', 760000, 15, 48],
            ['1015', 'Платформа и 4 сценария', 'ПАО «Енисей»', 'Норд', 'in_work', 9400000, 24, 61],
        ];

        // ещё 35 строк: высокому блоку есть чем заполниться (номера, компании и суммы — по кругу)
        $names = ['Учёт посетителей', 'Камеры на парковке', 'Досмотр грузовиков', 'Лицензии на год', 'Расширение сценариев', 'Контроль СИЗ', 'Поиск по лицам'];
        $companies = ['ООО «Орион»', 'АО «Сигма»', 'ООО «Каскад»', 'ПАО «Волга»', 'ООО «Меридиан»', 'АО «Кристалл»', 'ООО «Радуга»', 'ООО «Прогресс»'];
        $partners = ['ГК Восток', 'Луч', 'Алмаз', 'Норд', 'Ташкент-Софт'];
        $codes = ['in_work', 'in_work', 'won', 'in_work', 'lost'];

        for ($i = 0; $i < 35; $i++) {
            $sample[] = [
                (string) (1012 - $i * 3), $names[$i % 7], $companies[$i % 8], $partners[$i % 5], $codes[$i % 5],
                600000 + ($i * 7919 % 97) * 90000, 25 + $i * 4, 64 + $i * 6,
            ];
        }

        $rows = [];

        foreach ($sample as $i => [$number, $name, $company, $partner, $code, $amount, $updated_days, $days]) {
            $case = ProposalStatus::tryFrom($status ?? $code) ?? ProposalStatus::IN_WORK;
            $info = $case->data();
            $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];

            $rows[] = [
                'group' => 'sample-' . $i,
                'number' => $number,
                'name' => $name,
                'company' => $company,
                'partner' => $partner,
                'manager' => 'Анна Иванова',
                'status' => $case->value,
                'status_label' => $info['label'],
                'status_color' => $color,
                'status_icon' => $info['icon'],
                'amount' => (float) $amount,
                'symbol' => '₽',
                'date' => now()->subDays($days)->format('d.m.Y'),
                'updated' => now()->subDays($updated_days)->format('d.m.Y'),
                'days' => $days,
                'fresh' => $updated_days <= static::FRESH_DAYS,
                'url' => null,
                'status_url' => null,
                'deal_url' => null,
            ];
        }

        return $rows;
    }
}
