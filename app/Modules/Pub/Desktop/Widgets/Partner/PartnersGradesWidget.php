<?php

namespace App\Modules\Pub\Desktop\Widgets\Partner;

use App\Modules\Pub\Analytics\Services\PartnerScoringService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;

/**
 * Партнёры по грейдам (patch v30): сколько партнёров в каждом грейде и какая доля.
 *
 * Считается по полю partners.grade, подписи и цвет медали — из PartnerGrade.
 * Показатель в легенде «выигранные КП» берётся из PartnerScoringService::ranked()
 * за всю историю: там КП уже сведены по партнёрам, как на странице скоринга.
 */
class PartnersGradesWidget extends Widget
{
    public static function id(): string { return 'partners_grades'; }

    public static function name(): string { return 'Партнёры по грейдам'; }

    public static function category(): string { return 'partner'; }

    public static function description(): string
    {
        return 'Распределение партнёров по грейдам: количество в каждом и доли';
    }

    public static function icon(): string { return 'fa-medal'; }

    public static function sizes(): array { return ['4x4', '8x4']; }

    public static function defaultSize(): string { return '4x4'; }

    public static function order(): int { return 120; }

    public static function ttl(): int { return 900; }

    /** Показатель в легенде */
    public const METRICS = [
        'count' => 'Количество партнёров',
        'won' => 'Выигранные КП',
    ];

    public static function fields(): array
    {
        return [
            ['key' => 'only_active', 'type' => 'bool', 'label' => 'Только активные партнёры', 'default' => true],
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель в легенде', 'default' => 'count', 'options' => static::METRICS,
                'hint' => '«Выигранные КП» — вторым числом рядом с количеством'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // список партнёров с грейдами — страница скоринга; пустой год = вся история
        return route('analytics.partners', ['year' => '']);
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
            [PartnerGrade::PLATINUM, 4, 38],
            [PartnerGrade::GOLD, 9, 51],
            [PartnerGrade::SILVER, 14, 47],
            [PartnerGrade::BRONZE, 18, 22],
            [PartnerGrade::AGENT, 3, 5],
        ];
        $total = array_sum(array_column($sample, 1));

        $rows = [];
        foreach ($sample as [$grade, $count, $won]) {
            $data = $grade->data();
            $rows[] = [
                'key' => $grade->value, 'label' => $data['label'], 'hint' => $data['description'],
                'color' => $data['color']['medal'], 'count' => $count,
                'share' => $total > 0 ? $count / $total * 100 : 0.0, 'won' => $won, 'url' => null,
            ];
        }

        return ['total' => $total, 'won_total' => array_sum(array_column($sample, 2)), 'rows' => $rows];
    }

    /**
     * Распределение партнёров по грейдам
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['total', 'won_total', 'rows' => [['key', 'label', 'hint', 'color', 'count', 'share', 'won', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $counts = Partner::query()
            ->when(!empty($settings['only_active']), fn($query) => $query->where('active', 1))
            ->groupBy('grade')
            ->selectRaw('grade, COUNT(*) as cnt')
            ->pluck('cnt', 'grade')
            ->all();

        $total = (int) array_sum($counts);
        if ($total === 0) {
            return ['total' => 0, 'won_total' => 0, 'rows' => []];
        }

        // выигранные КП по партнёрам — из скоринга за всю историю
        $won_by_grade = [];
        if ((string) $settings['metric'] === 'won') {
            foreach (PartnerScoringService::ranked(null) as $row) {
                $key = (string) $row['grade_key'];
                $won_by_grade[$key] = ($won_by_grade[$key] ?? 0) + (int) $row['won'];
            }
        }

        // порядок грейдов — из перечисления, сверху самые весомые
        $order = [];
        foreach (PartnerGrade::cases() as $grade) {
            $order[$grade->value] = $grade->data();
        }

        $rows = [];
        foreach ($counts as $key => $count) {
            $key = (string) $key;
            $grade = $order[$key] ?? null;

            $rows[] = [
                'key' => $key,
                'label' => (string) ($grade['label'] ?? 'Без грейда'),
                'hint' => (string) ($grade['description'] ?? 'Грейд не заполнен или неизвестен'),
                'color' => (string) ($grade['color']['medal'] ?? ''),
                'sort' => (int) ($grade['sort'] ?? 999),
                'count' => (int) $count,
                'share' => $count / $total * 100,
                'won' => (int) ($won_by_grade[$key] ?? 0),
                // клик по грейду — список партнёров страницы скоринга с этим фильтром
                'url' => $grade === null ? null : route('analytics.partners', ['grade' => $key, 'year' => '']),
            ];
        }

        usort($rows, fn($a, $b) => [$b['count'], $a['sort']] <=> [$a['count'], $b['sort']]);

        return [
            'total' => $total,
            'won_total' => (int) array_sum(array_column($rows, 'won')),
            'rows' => $rows,
        ];
    }
}
