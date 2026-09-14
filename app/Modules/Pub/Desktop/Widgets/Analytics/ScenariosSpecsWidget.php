<?php

namespace App\Modules\Pub\Desktop\Widgets\Analytics;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Report\Services\ReportService;
use App\Modules\Pub\User\Models\User;

/**
 * Сценарии по спецификациям (patch v30) — отчёт /report/scenarios_specs на столе:
 * у какого клиента какой сценарий закуплен.
 *
 * Данные берёт сам отчёт — ReportService::getScenariosSpecsSummary(). Он строит
 * таблицу по лицензионным договорам: партнёр → клиент → КП → договор → спецификация →
 * сценарий → нейросервис, и склеивает одинаковые ячейки соседних строк (rowspan).
 * Виджет эти склейки разворачивает обратно и показывает либо строки отчёта,
 * либо сводку «сценарий → сколько клиентов».
 *
 * Фильтр отчёта здесь не предлагается: getScenariosSpecsSummary() принимает $filter,
 * но нигде его не применяет — отбор на странице на эту таблицу не влияет.
 */
class ScenariosSpecsWidget extends Widget
{
    /** Сколько колонок в строке отчёта */
    protected const COLUMNS = 7;

    public static function id(): string { return 'scenarios_specs'; }

    public static function name(): string { return 'Сценарии по спецификациям'; }

    public static function category(): string { return 'analytics'; }

    public static function description(): string
    {
        return 'У какого клиента какой сценарий закуплен — сводка отчёта';
    }

    public static function icon(): string { return 'fa-grid-2-plus'; }

    public static function sizes(): array { return ['16x8', '32x8', '8x8']; }

    public static function defaultSize(): string { return '16x8'; }

    public static function order(): int { return 120; }

    public static function ttl(): int { return 900; }

    public static function available(User $user): bool
    {
        // отчёт открыт всем, у кого есть доступ к порталу (пункт меню — general_access)
        return (bool) $user->can_do('general_access');
    }

    public static function fields(): array
    {
        return [
            ['key' => 'view', 'type' => 'select', 'label' => 'Разрез', 'default' => 'rows',
                'options' => ['rows' => 'Строки отчёта: клиент → сценарий', 'scenarios' => 'Сценарии: у скольких клиентов'],
                'hint' => 'Строки отчёта идут в его же порядке — по партнёру и клиенту'],
            ['key' => 'neuro', 'type' => 'bool', 'label' => 'Разбивать по нейросервисам', 'default' => false,
                'hint' => 'Отчёт даёт строку на каждый нейросервис сценария; без галочки они схлопнуты'],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько строк', 'default' => 20, 'min' => 3, 'max' => 100],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // у отчёта «Сценарии по спецификациям» нет именованного маршрута
        return url('/report/scenarios_specs');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 40 строк отчёта и 20 сценариев: высокому блоку есть чем заполниться
        $scenarios = ['Распознавание СИЗ (головной убор, куртка/жилет)', 'Распознавание ГРЗ', 'Строительная техника',
            'Подсчёт людей, пересекающих линию / в зоне', 'Детекция дыма и огня', 'Оставленные предметы',
            'Распознавание лиц', 'Скопление людей', 'Пересечение периметра', 'Контроль рабочего места',
            'Падение человека', 'Номера вагонов', 'Очередь на кассе', 'Курение в запрещённой зоне', 'Детекция драки',
            'Свободные парковочные места', 'Номера контейнеров', 'Мониторинг погрузки', 'Животные на дороге',
            'Тепловая карта посетителей'];
        $companies = ['ССР', 'Аврора', 'Мосводоканал', 'Северсталь', 'Ташкент-Сити', 'Логистик Групп',
            'ТЦ Мега', 'Порт Восточный', 'Аэропорт Пулково', 'Горэлектротранс'];
        $partners = ['OSMOVIEW', 'LURE IT', 'ВидеоМакс', 'Сфера-Интеграция'];
        $neuros = ['classification-head-wear', 'detection-car-plate', 'detection-machine', 'detection-person', 'detection-fire-smoke'];
        $view = (string) ($settings['view'] ?? 'rows') === 'scenarios' ? 'scenarios' : 'rows';

        $rows = [];
        if ($view === 'scenarios') {
            foreach ($scenarios as $i => $name) {
                $count = max(1, 9 - intdiv($i, 3));
                $rows[] = ['name' => $name, 'companies' => $count, 'specs' => $count + $i % 3];
            }
        } else {
            for ($i = 0; $i < 40; $i++) {
                $c = intdiv($i, 4);
                $rows[] = [
                    'partner' => $partners[$c % 4], 'company' => $companies[$c], 'spec' => 'Спецификация ' . ($c + 1),
                    'scenario' => $scenarios[($c * 3 + $i) % 20], 'neuro' => !empty($settings['neuro']) ? $neuros[$i % 5] : '',
                ];
            }
        }

        return [
            'view' => $view,
            'rows' => array_slice($rows, 0, (int) ($settings['limit'] ?? 20)), 'total' => count($rows),
            'companies' => count($companies), 'specs' => count($companies), 'scenarios' => count($scenarios),
            'partners' => count($partners),
        ];
    }

    /**
     * Строки отчёта «Сценарии по спецификациям» или сводка по сценариям
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['view', 'total', 'partners', 'companies', 'specs', 'scenarios',
     *     'rows' => строки отчёта ['partner', 'company', 'spec', 'scenario', 'neuro']
     *     либо сводка ['name', 'companies', 'specs']]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $view = (string) $settings['view'] === 'scenarios' ? 'scenarios' : 'rows';
        $neuro = !empty($settings['neuro']);
        $limit = (int) $settings['limit'];

        $lines = static::lines();

        $partners = $companies = $specs = $scenarios = [];
        foreach ($lines as $line) {
            $partners[$line['partner']] = true;
            $companies[$line['company']] = true;
            $specs[$line['company'] . '|' . $line['spec']] = true;
            if ($line['scenario'] !== '') $scenarios[$line['scenario']] = true;
        }

        $out = [
            'view' => $view,
            'total' => count($lines),
            'partners' => count($partners),
            'companies' => count($companies),
            'specs' => count($specs),
            'scenarios' => count($scenarios),
        ];

        if ($view === 'scenarios') {
            $out['rows'] = array_slice(static::byScenario($lines), 0, $limit);

            return $out;
        }

        // без разбивки по нейросервисам одинаковые строки схлопываются в одну
        if (!$neuro) {
            $unique = [];
            foreach ($lines as $line) {
                $line['neuro'] = '';
                $unique[$line['partner'] . '|' . $line['company'] . '|' . $line['spec'] . '|' . $line['scenario']] = $line;
            }
            $lines = array_values($unique);
        }

        $out['total'] = count($lines);
        $out['rows'] = array_slice($lines, 0, $limit);

        return $out;
    }

    /**
     * Строки отчёта с развёрнутыми склейками (rowspan): значение тянется из
     * предыдущей строки, как его видно глазами в таблице отчёта
     *
     * @return array [['partner', 'company', 'spec', 'scenario', 'neuro']]
     */
    protected static function lines(): array
    {
        $grid = ReportService::getScenariosSpecsSummary();
        $carry = array_fill(0, static::COLUMNS, '');
        $lines = [];

        foreach ($grid as $row) {
            for ($col = 0; $col < static::COLUMNS; $col++) {
                if (isset($row[$col])) {
                    $carry[$col] = static::plain($row[$col]['cell'] ?? '');
                }
            }

            $lines[] = [
                'partner' => $carry[0],
                'company' => $carry[1],
                'spec' => $carry[4] !== '' ? $carry[4] : $carry[3],
                'scenario' => $carry[5],
                'neuro' => $carry[6],
            ];
        }

        return $lines;
    }

    /**
     * Сводка «сценарий → сколько клиентов и спецификаций»
     *
     * @param array $lines
     * @return array [['name', 'companies', 'specs']]
     */
    protected static function byScenario(array $lines): array
    {
        $found = [];

        foreach ($lines as $line) {
            if ($line['scenario'] === '') continue;

            $found[$line['scenario']]['companies'][$line['company']] = true;
            $found[$line['scenario']]['specs'][$line['company'] . '|' . $line['spec']] = true;
        }

        $rows = [];
        foreach ($found as $name => $item) {
            $rows[] = ['name' => (string) $name, 'companies' => count($item['companies']), 'specs' => count($item['specs'])];
        }

        usort($rows, fn($a, $b) => [$b['companies'], $b['specs'], $a['name']] <=> [$a['companies'], $a['specs'], $b['name']]);

        return $rows;
    }

    /**
     * Ячейка отчёта как текст: в неё кладут и разметку (значок вместо КП,
     * список спецификаций через <br/>)
     *
     * @param mixed $cell
     * @return string
     */
    protected static function plain($cell): string
    {
        $text = str_ireplace(['<br/>', '<br>', '<br />'], ', ', (string) $cell);

        return trim(strip_tags($text));
    }
}
