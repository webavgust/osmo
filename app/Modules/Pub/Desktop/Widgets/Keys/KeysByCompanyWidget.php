<?php

namespace App\Modules\Pub\Desktop\Widgets\Keys;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ключи по компаниям (patch v30): где сосредоточены лицензии.
 *
 * Считаются ключи из карточек компаний (license_keys), разрез — компания, партнёр
 * договора или страна компании. Для каждой строки — число ключей, доля от всех
 * и ближайшая дата окончания среди ещё не истёкших ключей.
 */
class KeysByCompanyWidget extends Widget
{
    public static function id(): string
    {
        return 'keys_by_company';
    }

    public static function name(): string
    {
        return 'Ключи по компаниям';
    }

    public static function category(): string
    {
        return 'keys';
    }

    public static function description(): string
    {
        return 'Топ компаний по числу активных ключей и ближайшее окончание у каждой';
    }

    public static function icon(): string
    {
        return 'fa-building-lock';
    }

    public static function sizes(): array
    {
        return ['8x4', '8x8', '16x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 130;
    }

    public static function ttl(): int
    {
        return 900;
    }

    /** Разрезы: поле группировки, название и страница строки */
    public const MODES = [
        'company' => 'Компания',
        'partner' => 'Партнёр',
        'country' => 'Страна',
    ];

    public static function fields(): array
    {
        return [
            ['key' => 'by', 'type' => 'select', 'label' => 'Разрез', 'default' => 'company', 'options' => static::MODES],
            ['key' => 'only_active', 'type' => 'bool', 'label' => 'Только активные ключи', 'default' => true],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько строк готовить', 'default' => 40, 'min' => 3, 'max' => 100, 'hint' => 'Сколько строк влезет в блок — решает высота'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('analytics.licenses', array_filter(['only_active' => !empty($settings['only_active']) ? 1 : null]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 40 строк: высокому блоку должно быть чем заполниться; режется так же, как data() — по limit
        $names = ['ООО «Альфа»', 'АО «Вектор»', 'ООО «Гранит»', 'ЗАО «Дельта»', 'ООО «Енисей»', 'ООО «Жемчуг»', 'АО «Зенит»',
            'ООО «Ирбис»', 'ООО «Кедр»', 'АО «Лотос»', 'ООО «Магистраль»', 'ООО «Нева»', 'АО «Орион»', 'ООО «Пульсар»',
            'ООО «Радуга»', 'АО «Сатурн»', 'ООО «Тайга»', 'ООО «Урал-Сервис»', 'АО «Фрегат»', 'ООО «Хронос»'];
        $sample = [];
        for ($i = 0; $i < 40; $i++) {
            $sample[] = [$names[$i % 20] . ($i >= 20 ? ' (филиал)' : ''), max(1, 24 - (int) floor($i * .6)), [18, 44, 5, 120, 260, 9, 31, 75, 150, 200][$i % 10] + $i];
        }
        $total = array_sum(array_column($sample, 1));

        return [
            'rows' => array_map(fn($row) => [
                'id' => 0, 'name' => $row[0], 'count' => $row[1], 'share' => round($row[1] / $total * 100, 1),
                'nearest' => now()->addDays($row[2])->format('d.m.Y'), 'days' => $row[2], 'url' => null,
            ], array_slice($sample, 0, (int) $settings['limit'])),
            'total' => $total, 'groups' => count($sample), 'label' => static::MODES[$settings['by']] ?? 'Компания',
        ];
    }

    /**
     * Строки разреза, отсортированные по числу ключей
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'name', 'count', 'share', 'nearest', 'days', 'url']], 'total', 'groups', 'label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $by = (string) $settings['by'];
        $today = now()->startOfDay();

        $builder = DB::table('license_keys as k')
            ->leftJoin('companies as cm', 'cm.id', '=', 'k.company_id')
            ->leftJoin('contract_specifications as s', 's.id', '=', 'k.contract_specification_id')
            ->leftJoin('contracts as c', 'c.id', '=', 's.contract_id')
            ->leftJoin('partners as p', 'p.id', '=', 'c.partner_id')
            ->leftJoin('countries as co', 'co.id', '=', 'cm.country_id');

        if (!empty($settings['only_active'])) {
            $builder->where('k.active', 1);
        }

        [$id_field, $name_field] = match ($by) {
            'partner' => ['p.id', 'p.name'],
            'country' => ['co.id', 'co.name'],
            default => ['cm.id', 'cm.name'],
        };

        // ближайшее окончание — минимальная дата среди ещё не истёкших ключей группы
        $rows = $builder
            ->groupBy($id_field, $name_field)
            ->selectRaw($id_field . ' as group_id, ' . $name_field . ' as group_name, COUNT(*) as cnt, MIN(CASE WHEN k.active_to >= ? THEN k.active_to END) as nearest', [$today->format('Y-m-d')])
            ->orderByDesc('cnt')
            ->get();

        $total = (int) $rows->sum('cnt');

        $list = $rows->take((int) $settings['limit'])->map(function ($row) use ($by, $total, $today) {
            $nearest = $row->nearest ? Carbon::parse($row->nearest) : null;

            return [
                'id' => (int) $row->group_id,
                // ключи без договора остаются без партнёра, компании — без страны: это отдельная строка, а не пропуск
                'name' => (string) ($row->group_name ?: match ($by) {
                    'partner' => 'Без партнёра',
                    'country' => 'Без страны',
                    default => 'Без компании',
                }),
                'count' => (int) $row->cnt,
                'share' => $total > 0 ? round($row->cnt / $total * 100, 1) : 0.0,
                'nearest' => $nearest?->format('d.m.Y'),
                'days' => $nearest ? (int) $today->diffInDays($nearest->startOfDay(), false) : null,
                'url' => static::url($by, (int) $row->group_id),
            ];
        })->values()->all();

        return [
            'rows' => $list,
            'total' => $total,
            'groups' => $rows->count(),
            'label' => static::MODES[$by] ?? 'Компания',
        ];
    }

    /**
     * Страница строки: карточка компании или партнёра, для страны — реестр лицензий
     *
     * @param string $by
     * @param int $id
     * @return string|null
     */
    protected static function url(string $by, int $id): ?string
    {
        if ($id <= 0) return null;

        return match ($by) {
            'partner' => route('partner.detail', $id),
            'company' => route('company.detail', $id),
            default => null,
        };
    }
}
