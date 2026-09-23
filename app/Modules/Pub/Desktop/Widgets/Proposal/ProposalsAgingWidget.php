<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use App\Modules\Pub\User\Repositories\UserRepository;

/**
 * Возраст КП (patch v30): сколько КП «висят» в работе и как давно —
 * корзины по давности отправки с количеством в каждой.
 *
 * Считаются последние редакции со статусом «В работе» (v27), давность —
 * по дате отправки последней редакции (sended_at), как в виджете «Мои КП».
 * Последняя корзина — самая старая, поэтому всегда красная.
 */
class ProposalsAgingWidget extends Widget
{
    /** Наборы корзин: код => [подпись набора, границы в днях] */
    public const BUCKETS = [
        '7,30,90' => ['Неделя / месяц / квартал', [7, 30, 90]],
        '30,60,90' => ['30 / 60 / 90 дней', [30, 60, 90]],
        '14,30,60,90' => ['2 недели / 30 / 60 / 90 дней', [14, 30, 60, 90]],
        '30,90,180' => ['Месяц / квартал / полгода', [30, 90, 180]],
    ];

    /** Цвета корзин от свежих к старым; последняя корзина всегда красная */
    public const COLORS = ['success', 'primary', 'warning', 'danger'];

    public static function id(): string { return 'proposals_aging'; }

    public static function name(): string { return 'Возраст КП'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Сколько КП висят в работе: корзины по давности отправки, самая старая — красная';
    }

    public static function icon(): string { return 'fa-hourglass-half'; }

    public static function sizes(): array { return ['8x4', '4x2', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 220; }

    public static function ttl(): int { return 600; }

    public static function fields(): array
    {
        return [
            ['key' => 'buckets', 'type' => 'select', 'label' => 'Корзины', 'default' => '30,60,90',
                'options' => fn() => collect(static::BUCKETS)->map(fn($row) => $row[0])->all()],
            ['key' => 'manager', 'type' => 'select', 'label' => 'Менеджер', 'default' => 'all',
                'options' => fn() => static::managerOptions()],
            ['key' => 'show_oldest', 'type' => 'bool', 'label' => 'Самое старое КП', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    /**
     * Варианты настройки «Менеджер»: все, я, затем менеджеры с КП
     *
     * @return array
     */
    public static function managerOptions(): array
    {
        $options = ['all' => 'Все', 'mine' => 'Я'];

        foreach (UserRepository::getProposalAuthors() as $user) {
            $options[(string) $user->id] = (string) ($user->full_name ?: $user->name);
        }

        return $options;
    }

    /**
     * Границы корзин из настройки
     *
     * @param array $settings
     * @return array дни по возрастанию
     */
    public static function edges(array $settings): array
    {
        $key = (string) ($settings['buckets'] ?? '');

        return static::BUCKETS[$key][1] ?? static::BUCKETS['30,60,90'][1];
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
        $edges = static::edges($settings);
        $sample = [18, 11, 8, 7, 5];

        // корзин всегда на одну больше, чем границ
        $counts = array_map(fn($i) => $sample[$i] ?? 4, range(0, count($edges)));

        return static::pack($counts, $edges, 214, 41, 0, 'Все');
    }

    /**
     * Корзины по давности КП в работе
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['buckets' => [['label', 'count', 'share', 'color', 'hint']], 'total',
     *     'in_work', 'oldest', 'average', 'no_date', 'manager_label', 'url']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $edges = static::edges($settings);
        $manager = (string) $settings['manager'];

        $rows = ProposalsRecentWidget::applyStatus(
            ProposalStatusService::latestIterations(),
            ProposalStatus::IN_WORK->value
        );

        if ($manager === 'mine') {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $rows = $rows->filter(fn($row) => (int) $row->manager_id === $user_id);
        } elseif (ctype_digit($manager)) {
            $rows = $rows->filter(fn($row) => (int) $row->manager_id === (int) $manager);
        }

        $today = now()->startOfDay();
        $counts = array_fill(0, count($edges) + 1, 0);
        $days = [];
        $no_date = 0;

        foreach ($rows as $row) {
            if (empty($row->sended_at)) {
                $no_date++;
                continue;
            }

            $age = (int) $row->sended_at->copy()->startOfDay()->diffInDays($today);
            $days[] = $age;

            // корзина — первая граница, которую возраст не перешагнул; иначе последняя
            $index = count($edges);
            foreach ($edges as $i => $edge) {
                if ($age < $edge) {
                    $index = $i;
                    break;
                }
            }

            $counts[$index]++;
        }

        return static::pack(
            $counts,
            $edges,
            empty($days) ? null : max($days),
            empty($days) ? null : (int) round(array_sum($days) / count($days)),
            $no_date,
            static::managerOptions()[$manager] ?? 'Все'
        );
    }

    /**
     * Подписи, доли и цвета корзин
     *
     * @param array $counts количество по корзинам, от свежих к старым
     * @param array $edges границы в днях
     * @param int|null $oldest возраст самого старого КП
     * @param int|null $average средний возраст
     * @param int $no_date КП без даты отправки
     * @param string $manager_label подпись отбора по менеджеру
     * @return array
     */
    protected static function pack(array $counts, array $edges, ?int $oldest, ?int $average, int $no_date, string $manager_label): array
    {
        $total = array_sum($counts);
        $last = count($edges);
        $buckets = [];

        foreach ($counts as $i => $count) {
            $from = $i === 0 ? 0 : $edges[$i - 1];
            $to = $i === $last ? null : $edges[$i];

            $label = $to === null ? $edges[$last - 1] . '+ дн.' : ($i === 0 ? 'до ' . $to . ' дн.' : $from . '–' . $to . ' дн.');
            // последняя корзина всегда красная, остальные — по порядку от свежих
            $color = $i === $last ? 'danger' : (static::COLORS[min($i, count(static::COLORS) - 2)] ?? 'primary');

            $buckets[] = [
                'label' => $label,
                'count' => (int) $count,
                'share' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
                'color' => $color,
                'hint' => $to === null
                    ? 'В работе дольше ' . $from . ' дней'
                    : 'В работе от ' . $from . ' до ' . $to . ' дней',
            ];
        }

        return [
            'buckets' => $buckets,
            // total — КП в корзинах (от них доли), in_work — все КП в работе, с датой и без:
            // число «в работе» совпадает со счётчиком «КП по статусам» и списком КП
            'total' => $total,
            'in_work' => $total + $no_date,
            'oldest' => $oldest,
            'average' => $average,
            'no_date' => $no_date,
            'manager_label' => $manager_label,
            'url' => route('proposal.index'),
        ];
    }
}
