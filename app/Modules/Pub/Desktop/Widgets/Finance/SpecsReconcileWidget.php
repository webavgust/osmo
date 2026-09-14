<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Services\SpecReconcileService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Сверка спецификаций (patch v30) — у каких спецификаций сумма не сходится
 * с графиком платежей или с прикреплённым КП.
 *
 * Считает ровно то же, что метка «Расхождение» на карточке компании:
 * SpecReconcileService::check() с допусками из констант (spec_reconcile_tolerance,
 * spec_reconcile_hard_share). Отменённые спецификации не сверяются — у них
 * расхождение норма.
 *
 * Суммы расхождений пересчитываются в валюту виджета по текущему курсу
 * (расхождение ни к какой дате не привязано); спецификации, для которых курса нет,
 * в суммы не попадают и считаются в skipped.
 */
class SpecsReconcileWidget extends Widget
{
    /** Сколько строк держим в данных — вьюха режет их по высоте блока */
    public const MAX_ROWS = 30;

    public static function id(): string
    {
        return 'specs_reconcile';
    }

    public static function name(): string
    {
        return 'Сверка спецификаций';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Спецификации, у которых сумма не сходится с платежами или КП';
    }

    public static function icon(): string
    {
        return 'fa-scale-unbalanced';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x4', '16x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 160;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 900;
    }

    public static function available(User $user): bool
    {
        // сверка живёт на карточке компании, а она открыта всем пользователям портала
        return true;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'level', 'type' => 'select', 'label' => 'Уровень расхождения', 'default' => 'any',
                'hint' => 'Жёсткое — расхождение больше доли spec_reconcile_hard_share от суммы',
                'options' => ['any' => 'Любое расхождение', 'hard' => 'Только жёсткие']],
            // по умолчанию — сколько влезет по высоте блока (не больше MAX_ROWS)
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько спецификаций в списке', 'default' => self::MAX_ROWS, 'min' => 1, 'max' => self::MAX_ROWS],
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'options' => fn() => ['all' => 'Все партнёры'] + static::partners()],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $partner = (string) ($settings['partner'] ?? 'all');

        // метка «Расхождение» стоит у спецификаций на карточке компании;
        // выбран партнёр — ведём на его карточку со списком компаний и договоров
        return $partner !== 'all'
            ? route('partner.detail', (int) $partner)
            : route('company.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // образец на MAX_ROWS спецификаций — чтобы высокий блок было чем заполнить
        $companies = ['ООО «Гранит»', 'АО «Вектор»', 'ООО «Альфа»', 'RASHMI', 'ООО «Северсталь-Инфо»', 'ГК Восток',
            'МТ Интеграция', 'ООО «Телеком-Мастер»', 'Риверсистемс', 'Digital Wave Ltd', 'СТК', 'Shanghai Hengde Science & Tech Co'];
        $specs = ['С.3 Лицензии', 'С.2 Внедрение', 'С.1 Оборудование', 'С.4 Поддержка на 2026 год', 'С.5 Доработки', 'С.1 (часть 2)'];

        $rows = [];
        for ($i = 0; $i < static::MAX_ROWS; $i++) {
            $hard = $i < 12;
            // жёсткие сверху и по убыванию расхождения — как сортирует data()
            $diff = ($i % 3 === 0 ? 1 : -1) * ($hard ? (40 - $i) * 52000.0 : (40 - $i) * 900.0);
            // сумма не меньше расхождения — платежи не уходят в минус
            $amount = (($i * 13) % 20 + 3) * 210000.0 + abs($diff);
            $reason = $i % 5 === 1
                ? 'График платежей пуст: спецификация не разложена по платежам'
                : 'Платежи расходятся со спецификацией на ' . ($diff > 0 ? '+' : '−') . number_format(abs($diff), 0, ',', ' ') . ' ₽';

            $rows[] = [
                'company' => $companies[$i % count($companies)], 'spec' => $specs[$i % count($specs)], 'partner' => 'ГК Восток',
                'amount' => $amount, 'payments' => $amount + $diff, 'diff' => $diff,
                'hard' => $hard, 'reason' => $reason, 'reasons' => [$reason], 'url' => null,
            ];
        }

        return [
            'rows' => $rows,
            'count' => count($rows), 'hard' => 12, 'checked' => 184, 'skipped' => 0,
            'diff' => array_sum(array_map(fn($row) => abs($row['diff']), $rows)), 'symbol' => '₽',
        ];
    }

    /**
     * Спецификации с расхождением и итоги сверки
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows', 'count', 'hard', 'checked', 'skipped', 'diff', 'symbol']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $partner = (string) ($settings['partner'] ?? 'all');
        $hard_only = (string) ($settings['level'] ?? 'any') === 'hard';

        $specs = ContractSpecification::query()
            ->with(['payments', 'currency', 'company', 'contract.partner'])
            ->when($partner !== 'all', fn($query) => $query->whereHas(
                'contract',
                fn($query) => $query->where('partner_id', (int) $partner)
            ))
            ->get();

        $list = [];
        $total = ['count' => 0, 'hard' => 0, 'checked' => 0, 'skipped' => 0, 'diff' => 0.0];

        foreach ($specs as $spec) {
            $check = SpecReconcileService::check($spec);

            // отменённые не сверяются — они и в счётчик проверенных не идут
            if (!empty($check['skip'])) continue;

            $total['checked']++;

            if (!empty($check['ok'])) continue;
            if ($hard_only && empty($check['hard'])) continue;

            // расхождение ни к какой дате не привязано — берём текущий курс
            $diff = CurrencyService::convertAmount((float) $check['diff_payments'], $spec->currency_slug, $currency, now());
            $amount = CurrencyService::convertAmount((float) $check['amount'], $spec->currency_slug, $currency, now());
            $payments = CurrencyService::convertAmount((float) $check['payments'], $spec->currency_slug, $currency, now());

            $total['count']++;
            if (!empty($check['hard'])) $total['hard']++;

            if ($diff === null) {
                $total['skipped']++;
            } else {
                $total['diff'] += abs($diff);
            }

            $list[] = [
                'company' => (string) ($spec->company?->name ?: '—'),
                'spec' => (string) ($spec->name ?: '—'),
                'partner' => (string) ($spec->contract?->partner?->name ?: ''),
                'amount' => $amount === null ? null : round($amount, 2),
                'payments' => $payments === null ? null : round($payments, 2),
                'diff' => $diff === null ? null : round($diff, 2),
                'hard' => (bool) $check['hard'],
                // первая причина — в строку, остальные в подсказку
                'reason' => (string) (reset($check['reasons']) ?: ''),
                'reasons' => array_values($check['reasons']),
                'url' => $spec->company_id ? route('company.detail', $spec->company_id) : null,
            ];
        }

        // жёсткие сверху, дальше по величине расхождения
        usort($list, fn($a, $b) => [$b['hard'], abs((float) $b['diff'])] <=> [$a['hard'], abs((float) $a['diff'])]);

        return [
            'rows' => array_slice($list, 0, static::MAX_ROWS),
            'count' => $total['count'],
            'hard' => $total['hard'],
            'checked' => $total['checked'],
            'skipped' => $total['skipped'],
            'diff' => round($total['diff'], 2),
            'symbol' => $ctx->symbol($currency),
        ];
    }

    /**
     * Партнёры, у которых есть договоры.
     *
     * Свой список, а не PaymentCalendarService::partners(): там партнёр появляется
     * только вместе с платежами, а спецификация без единого платежа — как раз
     * то расхождение, ради которого этот виджет и сделан.
     *
     * @return array id => название
     */
    public static function partners(): array
    {
        return collect(DB::table('contracts as c')
            ->join('partners as pa', 'pa.id', '=', 'c.partner_id')
            ->distinct()
            ->orderBy('pa.name')
            ->get(['pa.id', 'pa.name']))
            ->mapWithKeys(fn($partner) => [(string) $partner->id => (string) $partner->name])
            ->all();
    }
}
