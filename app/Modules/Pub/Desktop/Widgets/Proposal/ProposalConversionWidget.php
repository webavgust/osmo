<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Конверсия КП (patch v30): доля выигранных среди решённых за период
 * и динамика к прошлому такому же отрезку.
 *
 * Считается по последним редакциям групп (ProposalStatusService::latestIterations()),
 * статусы — три из v27: «В работе», «Выиграно», «Проиграно». Решённые — те,
 * у кого статус финальный (выиграно и проиграно). Дата решения — MetricRegistry::decidedDates():
 * у выигранных — первая оплата по спецификации (без неё — смена статуса), у проигранных —
 * status_changed_at, а если её нет (старые записи) — дата отправки.
 * Прошлый отрезок идущего периода — по то же число (DesktopContext::previousRange(), $to_date).
 * Сумма выигранных — основные варианты через MetricRegistry::mainSum().
 * Спарклайн — 12 месяцев до конца периода, но не дальше текущего месяца; месяц без
 * решённых КП — разрыв линии, а не ноль.
 */
class ProposalConversionWidget extends Widget
{
    /** Сколько месяцев показывает спарклайн */
    public const SPARK_MONTHS = 12;

    public static function id(): string { return 'proposal_conversion'; }

    public static function name(): string { return 'Конверсия КП'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Доля выигранных среди решённых КП за период, динамика и сумма выигранных';
    }

    public static function icon(): string { return 'fa-percent'; }

    public static function sizes(): array { return ['4x2', '8x4']; }

    public static function defaultSize(): string { return '4x2'; }

    public static function order(): int { return 110; }

    public static function usesPeriod(): bool { return true; }

    public static function usesCurrency(): bool { return true; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои (менеджер — я)', 'default' => false],
            ['key' => 'show_sum', 'type' => 'bool', 'label' => 'Сумма выигранных', 'default' => true],
            ['key' => 'spark', 'type' => 'bool', 'label' => 'Спарклайн по месяцам', 'default' => true,
                'hint' => 'Конверсия за последние ' . self::SPARK_MONTHS . ' месяцев; виден в высоком блоке'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // период считается без базы, символ валюты в образце фиксированный
        $period = $ctx->periodFor($settings);

        return [
            'conversion' => 62.5,
            'previous' => 54.0,
            'previous_resolved' => 20,
            'prev_dates' => DesktopContext::previousPeriod($period['key'], true)['dates'],
            'delta' => 8.5,
            'won' => 15,
            'lost' => 9,
            'resolved' => 24,
            'amount' => 18400000.0,
            'symbol' => '₽',
            'label' => $period['label'],
            'dates' => $period['dates'],
            'spark' => [41.0, 50.0, 44.0, 55.0, 48.0, 60.0, 57.0, 52.0, 63.0, 58.0, 66.0, 62.5],
            'spark_labels' => ['окт', 'ноя', 'дек', 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен'],
        ];
    }

    /**
     * Конверсия за период, прошлый отрезок и сумма выигранных
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['conversion', 'previous', 'previous_resolved', 'prev_dates', 'delta', 'won', 'lost', 'resolved', 'amount', 'symbol', 'label', 'dates', 'spark', 'spark_labels']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        // решения — факт: прошлый отрезок идущего периода берётся по то же число
        $prev = DesktopContext::previousPeriod($period['key'], true);

        $all = static::scope($settings, $ctx);
        $dates = MetricRegistry::decidedDates($all);
        $resolved = static::resolvedBetween($all, $dates, $period['from'], $period['to']);
        $won = $resolved->filter(fn($row) => $row->status === ProposalStatus::WON->value)->values();

        // в прошлом отрезке могло не быть ни одного решённого КП — тогда сравнивать не с чем
        $prev_resolved = static::resolvedBetween($all, $dates, $prev['from'], $prev['to']);
        $previous = $prev_resolved->isEmpty() ? null : ProposalStatusService::conversion($prev_resolved);
        $conversion = ProposalStatusService::conversion($resolved);

        // спарклайн кончается месяцем конца периода, но не позже текущего: у «Текущего года»
        // месяцы после сегодняшнего пустые, и нулями они рисовали бы провал конверсии
        $spark_end = $period['to']->copy()->min(now());

        return [
            'conversion' => $conversion,
            'previous' => $previous,
            'previous_resolved' => $prev_resolved->count(),
            'prev_dates' => $prev['dates'],
            // решённых в периоде нет — конверсии нет, и сравнивать нечего
            'delta' => $previous === null || $resolved->isEmpty() ? null : round($conversion - $previous, 1),
            'won' => $won->count(),
            'lost' => $resolved->count() - $won->count(),
            'resolved' => $resolved->count(),
            'amount' => $settings['show_sum'] ? round(MetricRegistry::mainSum($won, $currency), 2) : null,
            'symbol' => $ctx->symbol($currency),
            'label' => $period['label'],
            'dates' => $period['dates'],
            'spark' => $settings['spark'] ? static::spark($all, $dates, $spark_end) : [],
            'spark_labels' => $settings['spark'] ? static::sparkLabels($spark_end) : [],
        ];
    }

    /**
     * Последние редакции КП с учётом настройки «только мои»
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return Collection
     */
    protected static function scope(array $settings, DesktopContext $ctx): Collection
    {
        $rows = ProposalStatusService::latestIterations();

        if (!empty($settings['mine'])) {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $rows = $rows->filter(fn($row) => (int) $row->manager_id === $user_id);
        }

        return $rows->values();
    }

    /**
     * Решённые КП (выиграно и проиграно), решение по которым принято в отрезке.
     * Дата решения — из MetricRegistry::decidedDates()
     *
     * @param Collection $rows
     * @param array $dates код группы => дата решения
     * @param Carbon $from
     * @param Carbon $to
     * @return Collection
     */
    protected static function resolvedBetween(Collection $rows, array $dates, Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        return $rows->filter(function ($row) use ($dates, $from, $to) {
            $status = ProposalStatus::tryFrom((string) $row->status);
            if (!$status || !$status->isFinal()) return false;

            $date = $dates[(string) $row->group] ?? null;

            return $date !== null && $date->between($from, $to);
        })->values();
    }

    /**
     * Конверсия по месяцам, заканчивая месяцем конца периода.
     * Месяц без решённых КП — null (разрыв линии), а не 0: ноль читался бы как провал
     *
     * @param Collection $rows
     * @param array $dates код группы => дата решения
     * @param Carbon $to последний месяц
     * @return array значения 0..100 или null; пусто — ни в одном месяце решённых нет
     */
    protected static function spark(Collection $rows, array $dates, Carbon $to): array
    {
        $values = [];

        foreach (static::months($to) as $month) {
            $resolved = static::resolvedBetween($rows, $dates, $month->copy()->startOfMonth(), $month->copy()->endOfMonth());
            $values[] = $resolved->isEmpty() ? null : ProposalStatusService::conversion($resolved);
        }

        return array_filter($values, fn($value) => $value !== null) === [] ? [] : $values;
    }

    /**
     * Подписи месяцев спарклайна
     *
     * @param Carbon $to
     * @return array
     */
    protected static function sparkLabels(Carbon $to): array
    {
        return array_map(fn(Carbon $month) => $month->translatedFormat('M'), static::months($to));
    }

    /**
     * Месяцы спарклайна по возрастанию
     *
     * @param Carbon $to
     * @return Carbon[]
     */
    protected static function months(Carbon $to): array
    {
        $months = [];

        for ($i = static::SPARK_MONTHS - 1; $i >= 0; $i--) {
            $months[] = $to->copy()->startOfMonth()->subMonthsNoOverflow($i);
        }

        return $months;
    }
}
