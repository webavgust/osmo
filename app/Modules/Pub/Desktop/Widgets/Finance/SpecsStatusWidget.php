<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Спецификации по статусам (patch v30) — сколько спецификаций в работе, закрыто
 * и отменено, и на какую сумму.
 *
 * Считаются сами спецификации (`contract_specifications.amount`), а не их платежи:
 * так же, как в отчёте «Конфигурации. Сводная». Год берётся по дате спецификации,
 * а если своей даты нет — по дате рамочного договора (как у ContractSpecification::date).
 *
 * Суммы пересчитываются в валюту виджета по текущему курсу (сумма спецификации
 * не привязана к дате поступления денег); спецификации, для которых курса нет,
 * в суммы не попадают и считаются в skipped.
 */
class SpecsStatusWidget extends Widget
{
    /** Значки статусов: в самом перечислении их нет, а на столе они нужны */
    public const ICONS = [
        'processing' => 'fa-gears',
        'closed' => 'fa-circle-check',
        'canceled' => 'fa-ban',
    ];

    public static function id(): string
    {
        return 'specs_status';
    }

    public static function name(): string
    {
        return 'Спецификации по статусам';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Сколько спецификаций в работе, закрыто и отменено — и на какую сумму';
    }

    public static function icon(): string
    {
        return 'fa-file-contract';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x2', '8x4'];
    }

    public static function defaultSize(): string
    {
        return '8x2';
    }

    public static function order(): int
    {
        return 150;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 600;
    }

    public static function available(User $user): bool
    {
        // отчёт «Конфигурации. Сводная» открыт всем пользователям портала
        return true;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'year', 'type' => 'select', 'label' => 'Год', 'default' => 'all',
                'hint' => 'По дате спецификации, а если своей нет — по дате договора',
                'options' => fn() => ['all' => 'Все годы'] + collect(static::years())
                    ->mapWithKeys(fn($year) => [(string) $year => (string) $year])
                    ->all()],
            ['key' => 'type', 'type' => 'select', 'label' => 'Тип договора', 'default' => 'all',
                'options' => fn() => ['all' => 'Все типы'] + collect(ContractType::getActualDecorated())
                    ->map(fn($type) => (string) $type['label'])
                    ->all()],
            ['key' => 'signed', 'type' => 'bool', 'label' => 'Показывать подписанные', 'default' => true,
                'hint' => 'Отметка «подписана» у спецификаций, кроме отменённых'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // отбор отчёта живёт в кэше фильтра, а не в адресе — ведём на сам отчёт
        return route('report.specs');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $sample = [
            ['processing', 9, 30019238.0],
            ['closed', 48, 69317258.0],
            ['canceled', 11, 5854802.0],
        ];

        $statuses = [];
        foreach ($sample as [$code, $count, $amount]) {
            $statuses[] = static::decorate($code) + ['count' => $count, 'amount' => $amount];
        }

        return [
            'statuses' => $statuses,
            'total' => 68, 'amount' => 105191298.0,
            'signed' => 51, 'signed_amount' => 88420000.0, 'unsigned' => 6,
            'year' => 'Все годы', 'type' => 'Все типы',
            'symbol' => '₽', 'skipped' => 0,
        ];
    }

    /**
     * Счётчики и суммы спецификаций по статусам
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['statuses', 'total', 'amount', 'signed', 'signed_amount', 'unsigned', 'year', 'type', 'symbol', 'skipped']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $year = (string) ($settings['year'] ?? 'all');
        $type = (string) ($settings['type'] ?? 'all');

        $builder = DB::table('contract_specifications as s')
            ->leftJoin('contracts as c', 'c.id', '=', 's.contract_id')
            ->groupBy('s.status', 's.is_signed', 's.currency_slug')
            ->selectRaw('s.status, s.is_signed, s.currency_slug, COUNT(*) as cnt, SUM(s.amount) as amount');

        if ($year !== 'all') {
            $builder->whereRaw('YEAR(COALESCE(s.date_create, c.date)) = ?', [(int) $year]);
        }

        if ($type !== 'all') {
            $builder->where('c.type', $type);
        }

        $totals = [];
        $total = ['count' => 0, 'amount' => 0.0, 'signed' => 0, 'signed_amount' => 0.0, 'unsigned' => 0, 'skipped' => 0];

        foreach ($builder->get() as $group) {
            $code = (string) $group->status;
            $count = (int) $group->cnt;

            // сумма спецификации ни к какой дате не привязана — берём текущий курс
            $amount = CurrencyService::convertAmount((float) $group->amount, $group->currency_slug, $currency, now());

            $totals[$code] ??= ['count' => 0, 'amount' => 0.0];
            $totals[$code]['count'] += $count;
            $total['count'] += $count;

            if ($amount === null) {
                $total['skipped'] += $count;
            } else {
                $totals[$code]['amount'] += $amount;
                $total['amount'] += $amount;
            }

            // подпись считаем только у живых спецификаций: у отменённой она ничего не значит
            if ($code === ContractSpecificationStatus::CANCELED->value) continue;

            if ($group->is_signed) {
                $total['signed'] += $count;
                if ($amount !== null) $total['signed_amount'] += $amount;
            } else {
                $total['unsigned'] += $count;
            }
        }

        // порядок статусов — из самого перечисления
        $statuses = [];
        foreach (ContractSpecificationStatus::cases() as $case) {
            $code = $case->value;
            $statuses[] = static::decorate($code) + [
                'count' => $totals[$code]['count'] ?? 0,
                'amount' => round($totals[$code]['amount'] ?? 0.0, 2),
            ];
        }

        return [
            'statuses' => $statuses,
            'total' => $total['count'],
            'amount' => round($total['amount'], 2),
            'signed' => $total['signed'],
            'signed_amount' => round($total['signed_amount'], 2),
            'unsigned' => $total['unsigned'],
            'year' => $year === 'all' ? 'Все годы' : $year,
            'type' => $type === 'all' ? 'Все типы' : (ContractType::tryFrom($type)?->data()['label'] ?? $type),
            'symbol' => $ctx->symbol($currency),
            'skipped' => $total['skipped'],
        ];
    }

    /**
     * Подпись, цвет и значок статуса
     *
     * @param string $code
     * @return array ['code', 'label', 'color', 'icon']
     */
    protected static function decorate(string $code): array
    {
        $data = ContractSpecificationStatus::tryFrom($code)?->data() ?? [];

        return [
            'code' => $code,
            'label' => (string) ($data['label'] ?? $code),
            'color' => (string) ($data['color'] ?? 'secondary'),
            'icon' => static::ICONS[$code] ?? 'fa-file-contract',
        ];
    }

    /**
     * Годы, за которые есть спецификации (по своей дате или по дате договора)
     *
     * @return array
     */
    protected static function years(): array
    {
        $years = collect(DB::table('contract_specifications as s')
            ->leftJoin('contracts as c', 'c.id', '=', 's.contract_id')
            ->selectRaw('DISTINCT YEAR(COALESCE(s.date_create, c.date)) as y')
            ->pluck('y'))
            ->filter()
            ->map(fn($year) => (int) $year)
            ->sortDesc()
            ->values()
            ->all();

        return $years ?: [(int) now()->year];
    }
}
