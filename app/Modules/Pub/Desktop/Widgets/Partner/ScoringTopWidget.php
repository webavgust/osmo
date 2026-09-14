<?php

namespace App\Modules\Pub\Desktop\Widgets\Partner;

use App\Modules\Pub\Analytics\Services\PartnerScoringService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Скоринг партнёров: топ (patch v30) — первые места рейтинга страницы «Скоринг партнёров».
 * Место, балл и буква — из PartnerScoringService::ranked(), как на странице.
 */
class ScoringTopWidget extends Widget
{
    public static function id(): string { return 'scoring_top'; }

    public static function name(): string { return 'Скоринг партнёров: топ'; }

    public static function category(): string { return 'partner'; }

    public static function description(): string
    {
        return 'Лучшие партнёры рейтинга: место, балл и оценка';
    }

    public static function icon(): string { return 'fa-ranking-star'; }

    public static function sizes(): array { return ['8x8', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 100; }

    public static function ttl(): int { return 900; }

    public static function fields(): array
    {
        return [
            ['key' => 'year', 'type' => 'select', 'label' => 'Год', 'default' => 'auto', 'hint' => '«Текущий» — последний год с данными, как на странице скоринга',
                'options' => fn() => ['auto' => 'Текущий'] + collect(PartnerScoringService::years())
                    ->mapWithKeys(fn($year) => [(string) $year => (string) $year])
                    ->all()],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько партнёров', 'default' => 30, 'min' => 3, 'max' => 30,
                'hint' => 'Сколько строк влезет в блок — решает высота; не влезшие считаются в «ещё»'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $year = (string) ($settings['year'] ?? 'auto');

        return route('analytics.partners', $year !== 'auto' ? ['year' => $year] : []);
    }

    /**
     * Год рейтинга: «Текущий» — последний год с данными (так страница выбирает год по умолчанию)
     *
     * @param array $settings
     * @return int|null
     */
    public static function year(array $settings): ?int
    {
        $year = (string) ($settings['year'] ?? 'auto');
        if ($year !== 'auto') return (int) $year;

        $years = PartnerScoringService::years();

        return empty($years) ? null : (int) $years[0];
    }

    /**
     * Образцовые данные: 30 мест рейтинга, чтобы было чем заполнить высокий блок;
     * отбор по «Сколько партнёров» — как у живых данных
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $rows = [];
        $names = ['ГК Восток', 'Ташкент-Софт', 'Луч', 'Алмаз', 'Норд', 'Вектор', 'Инфосистемы', 'Сибинтек',
            'Прагма', 'Горизонт', 'Контур', 'Меридиан', 'Альтаир', 'Спектр', 'Технопарк', 'Бизнес-Решения',
            'Интегра', 'Эталон', 'Форсайт', 'Орион', 'Квант', 'Сигма', 'Атлант', 'Гранит', 'Ладога',
            'Урал-Софт', 'Каспий', 'Байкал', 'Азимут', 'Полюс'];

        $sample = [];
        foreach ($names as $i => $name) {
            $grade = match (true) {
                $i % 11 === 10 => null,
                $i < 2 => 'Gold',
                $i < 8 => 'Silver',
                $i < 18 => 'Bronze',
                default => 'Agent',
            };
            $sample[] = [$name, 87 - (int) round($i * 1.9), $grade];
        }
        $sample = array_slice($sample, 0, (int) $settings['limit']);

        foreach ($sample as $i => [$name, $score, $grade]) {
            $rank = PartnerScoringService::rank($score);
            $rows[] = [
                'place' => $i + 1, 'partner_id' => 0, 'name' => $name, 'score' => $score,
                'rank_letter' => $rank['letter'], 'rank_label' => $rank['label'], 'rank_color' => $rank['color'],
                'grade' => $grade, 'url' => null,
            ];
        }

        return ['year' => (int) now()->year, 'total' => 48, 'rows' => $rows];
    }

    /**
     * Первые строки рейтинга
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['year', 'total', 'rows' => [['place', 'partner_id', 'name', 'score', 'rank_letter', 'rank_label', 'rank_color', 'grade', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $year = static::year($settings);
        $ranked = PartnerScoringService::ranked($year);

        $rows = $ranked->take((int) $settings['limit'])
            ->map(fn($row) => [
                'place' => (int) $row['place'],
                'partner_id' => (int) $row['partner']->id,
                'name' => (string) $row['partner']->name,
                'score' => (int) round($row['score']),
                'rank_letter' => $row['rank']['letter'],
                'rank_label' => $row['rank']['label'],
                'rank_color' => $row['rank']['color'],
                'grade' => $row['grade']['label'] ?? null,
                'url' => route('partner.detail', $row['partner']->id),
            ])
            ->values()
            ->all();

        return ['year' => $year, 'total' => $ranked->count(), 'rows' => $rows];
    }
}
