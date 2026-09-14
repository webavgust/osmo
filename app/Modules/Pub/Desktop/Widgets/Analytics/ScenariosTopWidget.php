<?php

namespace App\Modules\Pub\Desktop\Widgets\Analytics;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Популярные сценарии (patch v30) — какие сценарии чаще всего попадают
 * в коммерческие предложения и в спецификации договоров.
 *
 * Считается так же, как это видно на отчётах портала:
 *  - «по КП» — строки сценариев основного варианта КП (proposal_variant_scenarios
 *    у варианта с is_main), период берётся по дате создания КП;
 *  - «по спецификациям» — строки сценариев спецификаций
 *    (contract_specification_scenarios), период — по дате спецификации.
 * Строки без привязки к справочнику сценариев (вписанные руками) в счёт не идут:
 * у них нет scenario_id, и назвать их «сценарием справочника» нельзя.
 *
 * Клик по заголовку — отчёт «Список сценариев» (/report/scenarios).
 */
class ScenariosTopWidget extends Widget
{
    public static function id(): string { return 'scenarios_top'; }

    public static function name(): string { return 'Популярные сценарии'; }

    public static function category(): string { return 'analytics'; }

    public static function description(): string
    {
        return 'Сценарии, которые чаще других попадают в КП и спецификации';
    }

    public static function icon(): string { return 'fa-list-ol'; }

    public static function sizes(): array { return ['8x8', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 100; }

    public static function usesPeriod(): bool { return true; }

    public static function ttl(): int { return 900; }

    public static function available(User $user): bool
    {
        // отчёты открыты всем, у кого есть доступ к порталу (пункт меню — general_access)
        return (bool) $user->can_do('general_access');
    }

    public static function fields(): array
    {
        return [
            ['key' => 'source', 'type' => 'select', 'label' => 'Считать по', 'default' => 'proposals',
                'options' => ['proposals' => 'КП (основной вариант)', 'specs' => 'Спецификациям'],
                'hint' => 'Сценарии, вписанные руками мимо справочника, не считаются'],
            ['key' => 'scope', 'type' => 'select', 'label' => 'Отбор', 'default' => 'all',
                'options' => ['all' => 'За всё время', 'period' => 'За период'],
                'hint' => 'Дата КП — дата создания, дата спецификации — её собственная'],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько сценариев', 'default' => 10, 'min' => 3, 'max' => 30],
            ['key' => 'show_units', 'type' => 'bool', 'label' => 'Показывать количество лицензий', 'default' => true,
                'hint' => 'Только для КП: сумма поля «количество» в строках сценариев'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // у отчёта «Список сценариев» нет именованного маршрута
        return url('/report/scenarios');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $sample = [
            ['Распознавание марки и модели машин', 'Транспорт', 116, 2668],
            ['Подсчёт машин, пересекающих линию / в зоне', 'Транспорт', 107, 4774],
            ['Присутствие / отсутствие человека в зоне', 'Безопасность', 107, 25168],
            ['Распознавание ГРЗ', 'Транспорт', 104, 1810],
            ['Подсчёт людей, пересекающих линию / в зоне', 'Люди', 97, 87239],
        ];

        // до 30 сценариев (предел настройки «Сколько сценариев»): высокому блоку есть чем заполниться
        $more = ['Распознавание лиц', 'Детекция дыма и огня', 'Оставленные предметы', 'Контроль СИЗ: каска',
            'Очередь на кассе', 'Нарушение ПДД: стоп-линия', 'Скопление людей', 'Проход через турникет',
            'Пол и возраст посетителя', 'Свободные парковочные места', 'Падение человека', 'Вождение без ремня',
            'Номера вагонов', 'Детекция оружия', 'Курение в запрещённой зоне', 'Контроль рабочего места',
            'Выезд на встречную полосу', 'Животные на дороге', 'Мониторинг погрузки', 'Номера контейнеров',
            'Заполненность полок', 'Пересечение периметра', 'Тепловая карта посетителей',
            'Телефон за рулём', 'Детекция драки'];
        $groups = ['Безопасность', 'Люди', 'Транспорт', 'Промышленность', 'Ритейл'];
        foreach ($more as $i => $name) {
            $sample[] = [$name, $groups[$i % 5], 92 - $i * 3, 1200 + ($i * 2371) % 9000];
        }

        $has_units = (string) ($settings['source'] ?? 'proposals') !== 'specs' && !empty($settings['show_units'] ?? true);
        $total = array_sum(array_column($sample, 2)) + 180;
        $rows = [];

        foreach (array_slice($sample, 0, (int) ($settings['limit'] ?? 10)) as $i => [$name, $group, $uses, $units]) {
            $rows[] = [
                'place' => $i + 1, 'name' => $name, 'group' => $group,
                'uses' => $uses, 'units' => $has_units ? $units : null,
                'share' => round($uses * 100 / $total, 1),
            ];
        }

        return [
            'rows' => $rows, 'total' => $total, 'scenarios' => 41,
            'units' => $has_units ? 121659 : null, 'has_units' => $has_units,
            'source_label' => 'в КП', 'scope_label' => 'за всё время',
            'dates' => $ctx->periodFor($settings)['dates'],
        ];
    }

    /**
     * Топ сценариев по числу упоминаний
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['place', 'name', 'group', 'uses', 'units', 'share']], 'total',
     *     'scenarios', 'units', 'has_units', 'source_label', 'scope_label', 'dates']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $proposals = (string) $settings['source'] !== 'specs';
        $by_period = (string) $settings['scope'] === 'period';
        $period = $ctx->periodFor($settings);
        $has_units = $proposals && !empty($settings['show_units']);

        $query = $proposals
            ? static::proposalsQuery($has_units)
            : static::specsQuery();

        if ($by_period) {
            $query->whereBetween($proposals ? 'p.created_at' : 'cs.date_create', [$period['from'], $period['to']]);
        }

        $found = $query->groupBy('s.id', 's.name', 'g.name')
            ->orderByDesc('uses')
            ->orderBy('s.name')
            ->get();

        $total = (int) $found->sum('uses');
        $rows = [];

        foreach ($found->take((int) $settings['limit']) as $i => $row) {
            $rows[] = [
                'place' => $i + 1,
                'name' => (string) $row->name,
                'group' => (string) ($row->group_name ?? ''),
                'uses' => (int) $row->uses,
                'units' => $has_units ? (int) $row->units : null,
                'share' => $total > 0 ? round($row->uses * 100 / $total, 1) : 0.0,
            ];
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'scenarios' => $found->count(),
            'units' => $has_units ? (int) $found->sum('units') : null,
            'has_units' => $has_units,
            'source_label' => $proposals ? 'в КП' : 'в спецификациях',
            'scope_label' => $by_period ? mb_strtolower($period['label']) : 'за всё время',
            'dates' => $period['dates'],
        ];
    }

    /**
     * Сценарии основных вариантов КП
     *
     * @param bool $units считать количество лицензий
     * @return \Illuminate\Database\Query\Builder
     */
    protected static function proposalsQuery(bool $units)
    {
        return DB::table('proposal_variant_scenarios as pvs')
            ->join('proposal_variants as pv', 'pv.id', '=', 'pvs.proposal_variant_id')
            ->join('proposals as p', 'p.id', '=', 'pv.proposal_id')
            ->join('scenarios as s', 's.id', '=', 'pvs.scenario_id')
            ->leftJoin('scenario_groups as g', 'g.id', '=', 's.scenario_group_id')
            ->where('pv.is_main', 1)
            ->select('s.id', 's.name', DB::raw('g.name as group_name'), DB::raw('count(*) as uses'),
                DB::raw($units ? 'sum(pvs.count) as units' : '0 as units'));
    }

    /**
     * Сценарии спецификаций договоров
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected static function specsQuery()
    {
        return DB::table('contract_specification_scenarios as css')
            ->join('contract_specifications as cs', 'cs.id', '=', 'css.contract_specification_id')
            ->join('scenarios as s', 's.id', '=', 'css.scenario_id')
            ->leftJoin('scenario_groups as g', 'g.id', '=', 's.scenario_group_id')
            ->select('s.id', 's.name', DB::raw('g.name as group_name'), DB::raw('count(*) as uses'),
                DB::raw('0 as units'));
    }
}
