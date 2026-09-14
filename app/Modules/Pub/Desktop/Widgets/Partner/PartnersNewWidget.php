<?php

namespace App\Modules\Pub\Desktop\Widgets\Partner;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\EntityLog\Models\EntityLog;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use Illuminate\Support\Carbon;

/**
 * Новые партнёры и компании (patch v30): кто появился за период стола.
 *
 * Отбор по partners.created_at и companies.created_at, автор — из журнала
 * изменений (entity_logs, событие created): у записей старше журнала автора нет.
 */
class PartnersNewWidget extends Widget
{
    public static function id(): string { return 'partners_new'; }

    public static function name(): string { return 'Новые партнёры'; }

    public static function category(): string { return 'partner'; }

    public static function description(): string
    {
        return 'Партнёры и компании, появившиеся за период: сколько и кто именно';
    }

    public static function icon(): string { return 'fa-handshake-simple'; }

    public static function sizes(): array { return ['8x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 130; }

    public static function ttl(): int { return 900; }

    public static function usesPeriod(): bool { return true; }

    /** Что считать новым */
    public const TYPES = [
        'partner' => 'Партнёры',
        'company' => 'Компании',
        'both' => 'Партнёры и компании',
    ];

    public static function fields(): array
    {
        return [
            ['key' => 'type', 'type' => 'select', 'label' => 'Тип', 'default' => 'partner', 'options' => static::TYPES],
            ['key' => 'author', 'type' => 'bool', 'label' => 'Показывать, кто создал', 'default' => true, 'hint' => 'Из журнала изменений; у старых записей автора нет'],
            ['key' => 'compare', 'type' => 'bool', 'label' => 'Сравнить с предыдущим отрезком', 'default' => true],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько строк готовить', 'default' => 20, 'min' => 3, 'max' => 100, 'hint' => 'Сколько строк влезет в блок — решает высота'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return (string) ($settings['type'] ?? 'partner') === 'company'
            ? route('company.index')
            : route('partner.index');
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
        // 40 записей вперемешку: высокому блоку есть чем заполниться; тип и «Сколько строк» — как у живых данных
        $partners = ['Ташкент-Софт', 'Луч', 'Вектор', 'Прагма', 'Меридиан', 'Альтаир', 'Спектр', 'Интегра', 'Форсайт', 'Орион',
            'Квант', 'Сигма', 'Гранит', 'Ладога', 'Контур', 'Эталон', 'Атлант', 'Байкал', 'Азимут', 'Полюс'];
        $companies = ['ООО «Альфа»', 'АО «Северсталь-Инфо»', 'ООО «Техностиль»', 'ПАО «Энергосбыт»', 'ООО «Мегаполис»',
            'АО «Росгеология»', 'ООО «Стройинвест»', 'ООО «Дельта»', 'АО «Каспий-Нефть»', 'ООО «Орбита»', 'ООО «Гефест»',
            'АО «Транслайн»', 'ООО «Вертикаль»', 'АО «Промсвязь»', 'ООО «Лидер»', 'ООО «Кристалл»', 'АО «Норильск-Сервис»',
            'ООО «Статус»', 'ООО «Аврора»', 'АО «Ижора»'];
        $grades = ['Silver', 'Bronze', 'Agent', 'Gold'];
        $authors = ['Анна Петрова', 'Иван Смирнов', '', 'Ольга Кузнецова'];

        $all = [];
        for ($i = 0; $i < 40; $i++) {
            $company = $i % 2 === 1;
            $all[] = [
                'type' => $company ? 'company' : 'partner', 'id' => 0,
                'name' => $company ? $companies[intdiv($i, 2)] : $partners[intdiv($i, 2)],
                'note' => $company ? $partners[($i + 7) % count($partners)] : $grades[$i % count($grades)],
                'date' => now()->subDays($i * 2 + 1)->format('d.m.Y'),
                'author' => $authors[$i % count($authors)], 'url' => null,
            ];
        }

        $type = (string) ($settings['type'] ?? 'partner');
        $rows = array_values(array_filter($all, fn($row) => $type === 'both' || $row['type'] === $type));
        $count = count($rows);
        $partners_count = count(array_filter($rows, fn($row) => $row['type'] === 'partner'));

        return [
            'count' => $count, 'partners' => $partners_count, 'companies' => $count - $partners_count,
            'previous' => max(0, $count - 4), 'label' => DesktopContext::PERIODS['quarter'], 'dates' => '01.07.2026 – 30.09.2026',
            'rows' => array_slice($rows, 0, (int) $settings['limit']),
        ];
    }

    /**
     * Новые партнёры и компании за период
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['count', 'partners', 'companies', 'previous', 'label', 'dates',
     *     'rows' => [['type', 'id', 'name', 'note', 'date', 'author', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $period = $ctx->periodFor($settings);
        $type = (string) $settings['type'];

        $rows = $this->rows($period['from'], $period['to'], $type, (int) $settings['limit']);

        if (!empty($settings['author'])) {
            $this->fillAuthors($rows);
        }

        $previous = null;
        if (!empty($settings['compare'])) {
            [$prev_from, $prev_to] = DesktopContext::previousRange($period['key']);
            $previous = $this->count($prev_from, $prev_to, $type);
        }

        // разбивка считается запросами, а не по списку: список обрезан настройкой
        $partners = $type === 'company' ? 0 : $this->count($period['from'], $period['to'], 'partner');
        $companies = $type === 'partner' ? 0 : $this->count($period['from'], $period['to'], 'company');

        return [
            'count' => $partners + $companies,
            'partners' => $partners,
            'companies' => $companies,
            'previous' => $previous,
            'label' => $period['label'],
            'dates' => $period['dates'],
            'rows' => $rows,
        ];
    }

    /**
     * Строки списка: самые свежие сверху
     *
     * @param Carbon $from
     * @param Carbon $to
     * @param string $type
     * @param int $limit
     * @return array
     */
    protected function rows(Carbon $from, Carbon $to, string $type, int $limit): array
    {
        $rows = [];

        if ($type !== 'company') {
            $partners = Partner::query()
                ->whereBetween('created_at', [$from, $to])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            foreach ($partners as $partner) {
                $grade = PartnerGrade::tryFrom((string) $partner->grade)?->data();
                $rows[] = [
                    'type' => 'partner',
                    'id' => (int) $partner->id,
                    'name' => (string) $partner->name,
                    'note' => (string) ($grade['label'] ?? ''),
                    'date' => $partner->created_at?->format('d.m.Y') ?? '',
                    'sort' => (string) ($partner->created_at?->format('Y-m-d H:i:s') ?? ''),
                    'author' => '',
                    'url' => route('partner.detail', $partner->id),
                ];
            }
        }

        if ($type !== 'partner') {
            $companies = Company::query()
                ->with('partner')
                ->whereBetween('created_at', [$from, $to])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            foreach ($companies as $company) {
                $rows[] = [
                    'type' => 'company',
                    'id' => (int) $company->id,
                    'name' => (string) $company->name,
                    'note' => (string) ($company->partner?->name ?? ''),
                    'date' => $company->created_at?->format('d.m.Y') ?? '',
                    'sort' => (string) ($company->created_at?->format('Y-m-d H:i:s') ?? ''),
                    'author' => '',
                    'url' => route('company.detail', $company->id),
                ];
            }
        }

        usort($rows, fn($a, $b) => $b['sort'] <=> $a['sort']);

        return array_slice($rows, 0, $limit);
    }

    /**
     * Сколько появилось за отрезок
     *
     * @param Carbon $from
     * @param Carbon $to
     * @param string $type
     * @return int
     */
    protected function count(Carbon $from, Carbon $to, string $type): int
    {
        $count = 0;

        if ($type !== 'company') {
            $count += Partner::whereBetween('created_at', [$from, $to])->count();
        }

        if ($type !== 'partner') {
            $count += Company::whereBetween('created_at', [$from, $to])->count();
        }

        return $count;
    }

    /**
     * Проставить автора из журнала изменений (событие «Создание»)
     *
     * @param array $rows
     * @return void
     */
    protected function fillAuthors(array &$rows): void
    {
        if (empty($rows)) return;

        $ids = [];
        foreach ($rows as $row) {
            $ids[$row['type']][] = $row['id'];
        }

        $authors = [];
        foreach ($ids as $type => $list) {
            $logs = EntityLog::query()
                ->with('user')
                ->where('type', $type)
                ->where('event', EntityLog::EVENT_CREATED)
                ->whereIn('model_id', $list)
                ->orderBy('id')
                ->get();

            foreach ($logs as $log) {
                $authors[$type . ':' . (int) $log->model_id] = (string) ($log->user?->full_name ?? '');
            }
        }

        foreach ($rows as &$row) {
            $row['author'] = $authors[$row['type'] . ':' . $row['id']] ?? '';
        }
        unset($row);
    }
}
