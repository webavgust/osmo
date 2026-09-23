<?php

namespace App\Modules\Pub\Desktop\Widgets\Analytics;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Report\Services\ReportSpecService;
use App\Modules\Pub\User\Models\User;

/**
 * Конфигурации, сводная (patch v30) — итоги отчёта «Конфигурации. Сводная»
 * (/report/specs) одним блоком.
 *
 * Считается ровно то, что отчёт показывает в таблице: партнёры, клиенты, договоры,
 * спецификации, конфигурации проектов и строки состава (сценарии). Строки состава,
 * вписанные руками мимо справочника, отчёт подсвечивает — здесь они считаются отдельно.
 * Отбор тот же, что у отчёта: «отфильтрованные» (снятые галочкой спецификации
 * не показываются) или «все спецификации».
 *
 * Сумм и статусов в этом отчёте нет — они живут в виджете «Спецификации по статусам».
 */
class SpecsSummaryWidget extends Widget
{
    public static function id(): string { return 'specs_summary'; }

    public static function name(): string { return 'Конфигурации, сводная'; }

    public static function category(): string { return 'analytics'; }

    public static function description(): string
    {
        return 'Итоги отчёта по спецификациям: партнёры, договоры, конфигурации, состав';
    }

    public static function icon(): string { return 'fa-table-cells-large'; }

    public static function sizes(): array { return ['8x4', '8x8', '16x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 110; }

    public static function ttl(): int { return 900; }

    public static function available(User $user): bool
    {
        // отчёт открыт всем, у кого есть доступ к порталу (пункт меню — general_access)
        return (bool) $user->can_do('general_access');
    }

    public static function fields(): array
    {
        return [
            ['key' => 'mode', 'type' => 'select', 'label' => 'Отбор', 'default' => 'filtered',
                'options' => ['filtered' => 'Отфильтрованные', 'all' => 'Все спецификации'],
                'hint' => 'Как переключатель наверху отчёта'],
            ['key' => 'partners', 'type' => 'bool', 'label' => 'Список партнёров', 'default' => true,
                'hint' => 'Кто сколько спецификаций набрал; виден в высоком блоке'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('report.specs', ['mode' => (string) ($settings['mode'] ?? 'filtered')]);
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $rows = [
            ['ГК Восток', 14], ['Ташкент-Софт', 9], ['Луч', 7], ['Алмаз', 5], ['Норд', 3],
        ];

        // 40 партнёров: высокому блоку есть чем заполниться
        $names = ['Вектор', 'Горизонт', 'Спектр', 'Орион', 'Пульс', 'Сфера', 'Урал', 'Фокус', 'Эра',
            'Янтарь', 'Байкал', 'Меридиан', 'Азимут', 'Квант', 'Сигма'];
        $suffixes = [' Системы', '-Интеграция', ' Видео'];
        for ($i = 0; count($rows) < 40; $i++) {
            $rows[] = [$names[$i % 15] . $suffixes[intdiv($i, 15) % 3], max(1, 3 - intdiv($i, 12))];
        }

        $specs = array_sum(array_column($rows, 1));

        return [
            'specs' => $specs, 'partners' => count($rows), 'companies' => 58, 'contracts' => 71,
            'configurations' => $specs - 9, 'scenarios' => $specs * 2 + 17, 'manual' => 9,
            'mode_label' => (string) ($settings['mode'] ?? 'filtered') === 'all' ? 'все спецификации' : 'отфильтрованные',
            'rows' => array_map(fn($row) => ['name' => $row[0], 'specs' => $row[1]], $rows),
        ];
    }

    /**
     * Итоги отчёта «Конфигурации. Сводная»
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['specs', 'partners', 'companies', 'contracts', 'configurations',
     *     'scenarios', 'manual', 'mode_label', 'rows' => [['name', 'specs']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $mode = (string) $settings['mode'] === 'all' ? 'all' : 'filtered';
        $grid = ReportSpecService::specs($mode);

        $count = ['partners' => 0, 'companies' => 0, 'contracts' => 0, 'specs' => 0, 'configurations' => 0, 'scenarios' => 0, 'manual' => 0];
        $by_partner = [];
        $partner = '';

        // строки отчёта склеены по колонкам: непустая ячейка — начало новой группы
        foreach ($grid as $row) {
            if (!empty($row[0])) {
                $partner = (string) $row[0]['cell'];
                $count['partners']++;
            }
            if (!empty($row[1])) $count['companies']++;
            if (!empty($row[2])) $count['contracts']++;

            if (!empty($row[3])) {
                $count['specs']++;
                $by_partner[$partner] = ($by_partner[$partner] ?? 0) + 1;
            }

            if (!empty($row[4]['cell'])) $count['configurations']++;

            if (!empty($row[5]['cell'])) {
                $count['scenarios']++;
                if (!empty($row[5]['handle'])) $count['manual']++;
            }
        }

        arsort($by_partner);
        $rows = [];
        foreach ($by_partner as $name => $specs) {
            $rows[] = ['name' => (string) $name, 'specs' => (int) $specs];
        }

        return $count + [
            'mode_label' => $mode === 'all' ? 'все спецификации' : 'отфильтрованные',
            'rows' => $rows,
        ];
    }
}
