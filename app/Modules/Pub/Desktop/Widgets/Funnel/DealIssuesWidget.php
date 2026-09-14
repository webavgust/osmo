<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Models\CrmDealIssues;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Illuminate\Support\Str;

/**
 * Проблемы в сделках (patch v30): сделки Битрикс24 с незаполненными полями и причина.
 *
 * Считает ровно то же, что попап «Проблемные сделки» на странице воронки, —
 * CrmDealRepository::getDealWithIssues() по проверкам перечисления CrmDealIssues;
 * виджет только отбирает нужные типы проблем и менеджеров.
 *
 * Клик по сделке — карточка сделки в Битрикс24, чтобы сразу поправить поле.
 * Выборка тяжёлая (проверки идут по всем сделкам воронки), поэтому кэш длиннее
 * обычного.
 */
class DealIssuesWidget extends Widget
{
    public static function id(): string { return 'deal_issues'; }

    public static function name(): string { return 'Проблемы в сделках'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Сделки Битрикс24 с незаполненными полями и причина';
    }

    public static function icon(): string { return 'fa-triangle-exclamation'; }

    public static function sizes(): array { return ['8x4', '4x2', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 500; }

    public static function ttl(): int { return 1800; }

    public static function fields(): array
    {
        return [
            ['key' => 'types', 'type' => 'list', 'label' => 'Типы проблем', 'default' => [],
                'options' => fn() => static::typeOptions(), 'hint' => 'Пусто — все проверки портала'],
            ['key' => 'managers', 'type' => 'list', 'label' => 'Менеджеры', 'default' => [],
                'options' => fn() => ManagerQuarterWidget::managerOptions(), 'hint' => 'Пусто — все менеджеры'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('dashboard.index');
    }

    /**
     * Типы проблем для настройки: имя случая перечисления => подпись проверки
     *
     * @return array
     */
    public static function typeOptions(): array
    {
        $out = [];
        foreach (CrmDealIssues::cases() as $case) {
            $out[$case->name] = (string) ($case->data()['label'] ?? $case->name);
        }

        return $out;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // как в живых данных: сначала самые проблемные сделки, дальше по названию;
        // 40 сделок — высоким блокам должно быть чем заполниться
        $titles = ['Платформа Восток', 'Завод Прогресс', 'Пилот «Сибирь»', 'ТРК Одинцово', 'Порт Усть-Луга',
            'Складской комплекс «Юг»', 'Аэропорт Кольцово', 'Металлург-Инвест', 'Агрохолдинг «Нива»', 'Сеть АЗС «Трасса»'];
        $stages = ['Contracting', 'Presentation', 'Pilot project', 'TCP', 'Research'];
        $managers = ['Алексей Карасев', 'Александр Максимов', 'Олег Данненберг', 'Анна Август.'];
        $issues = ['Не указан конечный заказчик', 'Не указана стоимость сделки', 'Не указан партнёр',
            'Месяц и квартал не совпадают', 'Не заполнен месяц сделки или квартал'];

        $rows = [];
        for ($i = 0; $i < 40; $i++) {
            $list = [$issues[$i % 5]];
            if ($i < 6) $list[] = $issues[($i + 2) % 5];
            $rows[] = [
                $titles[$i % 10] . ($i >= 10 ? ' · ' . (intdiv($i, 10) + 1) : ''),
                $stages[$i % 5],
                $managers[$i % 4],
                $list,
            ];
        }

        return static::shape(array_map(fn($row, $i) => [
            'id' => 1000 + $i,
            'title' => $row[0],
            'stage' => $row[1],
            'manager' => $row[2],
            'issues' => $row[3],
            'url' => null,
        ], $rows, array_keys($rows)));
    }

    /**
     * Проблемные сделки и счётчики по типам проблем
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'title', 'stage', 'manager', 'issues', 'url']], 'counters', 'total', 'issue_total']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $types = array_map('strval', (array) $settings['types']);
        $managers = array_map('strval', (array) $settings['managers']);

        $rows = [];
        foreach (CrmDealRepository::getDealWithIssues() as $deal) {
            $assigned_by = (string) $deal->assigned_by;
            if ($managers && !in_array($assigned_by, $managers, true)) continue;

            // отбор по типам проблем: оставляем только выбранные проверки
            $issues = collect($deal->issues)
                ->filter(fn(CrmDealIssues $issue) => !$types || in_array($issue->name, $types, true))
                ->map(fn(CrmDealIssues $issue) => (string) ($issue->data()['label'] ?? $issue->name))
                ->values()
                ->all();

            if (empty($issues)) continue;

            $rows[] = [
                'id' => (int) $deal->id,
                'title' => (string) $deal->title,
                'stage' => (string) $deal->stage_name,
                'manager' => trim(Str::afterLast($assigned_by, ']')) ?: $assigned_by,
                'issues' => $issues,
                'url' => CrmDealRegistryService::url($deal->id),
            ];
        }

        // самые проблемные сделки сверху, дальше по названию
        usort($rows, fn($a, $b) => [count($b['issues']), $a['title']] <=> [count($a['issues']), $b['title']]);

        return static::shape($rows);
    }

    /**
     * Счётчики по типам проблем и итоги
     *
     * @param array $rows
     * @return array
     */
    protected static function shape(array $rows): array
    {
        $counters = [];
        foreach ($rows as $row) {
            foreach ($row['issues'] as $label) {
                $counters[$label] = ($counters[$label] ?? 0) + 1;
            }
        }
        arsort($counters);

        return [
            'rows' => $rows,
            'counters' => array_map(fn($label, $count) => ['label' => $label, 'count' => $count], array_keys($counters), $counters),
            'total' => count($rows),
            'issue_total' => array_sum($counters),
        ];
    }
}
