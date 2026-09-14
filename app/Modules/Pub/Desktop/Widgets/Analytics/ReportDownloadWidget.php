<?php

namespace App\Modules\Pub\Desktop\Widgets\Analytics;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Выгрузка отчёта (patch v30) — кнопка на готовую выгрузку портала в Excel.
 *
 * Список закрыт: только те выгрузки, которые в портале действительно есть и берутся
 * обычной ссылкой (ReportDownloadService, PhpSpreadsheet). Файл собирается на сервере
 * в storage/app/temp, поэтому виджет заодно показывает, когда такую выгрузку делали
 * в последний раз — по времени последнего файла с тем же именем.
 *
 * Имя файла у отчётов «Конфигурации. Сводная» и «Договоры и оплаты» одно и то же
 * (report-ГГГГММДД-ЧЧММСС.xlsx), так что дата у них общая — это дата последней
 * выгрузки любого из двух.
 *
 * PDF-кнопок здесь нет намеренно: у части отчётов PDF пишется в файл с чужим именем,
 * и «дата последней выгрузки» врала бы. За PDF — на саму страницу отчёта.
 * Выгрузка реестра сделок Битрикс24 тоже не здесь: она открывается попапом с формой,
 * и её кнопка живёт в виджете «Действие».
 */
class ReportDownloadWidget extends Widget
{
    /** Белый список выгрузок: ключ => [подпись, маршрут, начало имени файла, значок, страница отчёта] */
    public const REPORTS = [
        'specs' => ['Конфигурации, сводная', 'report-download.specs', 'report-', 'fa-table-cells-large', 'report.specs'],
        'payments' => ['Договоры и оплаты', 'report-download.payments', 'report-', 'fa-money-check-dollar', null],
        'industry' => ['Воронка: сферы и менеджеры', 'report-download.tbl_industry_name', 'tbl-industry-name-', 'fa-industry', 'dashboard.index'],
        'country_quarter' => ['Страны и статусы поквартально', 'report-download.tbl_country_status__quarter', 'tbl-country-status-quarter-', 'fa-earth-europe', 'dashboard.index'],
        'manager_quarter' => ['Менеджеры и статусы поквартально', 'report-download.tbl_manager_status__quarter', 'tbl-manager-status-quarter-', 'fa-user-tie', 'dashboard.index'],
        'country_month' => ['Страны и статусы помесячно', 'report-download.tbl_country_status__month', 'tbl-country-status-month-', 'fa-earth-europe', 'dashboard.index'],
        'status_month' => ['Статусы и страны помесячно', 'report-download.tbl_status_country__month', 'tbl-status-country-month-', 'fa-list-check', 'dashboard.index'],
    ];

    /** Сколько кнопок помещается в ряд, больше не имеет смысла */
    public const MAX = 4;

    public static function id(): string { return 'report_download'; }

    public static function name(): string { return 'Выгрузка отчёта'; }

    public static function category(): string { return 'analytics'; }

    public static function description(): string
    {
        return 'Кнопка на готовую выгрузку портала в Excel и дата прошлой выгрузки';
    }

    public static function icon(): string { return 'fa-file-excel'; }

    public static function sizes(): array { return ['4x2', '8x2', '8x4']; }

    public static function defaultSize(): string { return '4x2'; }

    public static function order(): int { return 140; }

    public static function ttl(): int { return 60; }

    public static function available(User $user): bool
    {
        // отчёты и воронка открыты всем, у кого есть доступ к порталу (пункты меню — general_access)
        return (bool) $user->can_do('general_access') && !empty(static::reports());
    }

    public static function fields(): array
    {
        return [
            ['key' => 'reports', 'type' => 'list', 'label' => 'Отчёты (до ' . self::MAX . ')', 'default' => ['specs'],
                'options' => fn() => collect(static::reports())->map(fn($report) => $report[0])->all(),
                'hint' => 'Первый — главная кнопка, остальные встают рядом в широком блоке'],
            ['key' => 'date', 'type' => 'bool', 'label' => 'Показывать дату последней выгрузки', 'default' => true,
                'hint' => 'Время последнего файла выгрузки на сервере'],
            ['key' => 'color', 'type' => 'select', 'label' => 'Цвет кнопки', 'default' => 'light-success',
                'options' => ['light-success' => 'Светло-зелёная', 'success' => 'Зелёная', 'light-primary' => 'Светло-синяя',
                    'primary' => 'Синяя', 'light' => 'Серая', 'dark' => 'Тёмная']],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $keys = static::chosen($settings);
        $page = static::REPORTS[reset($keys) ?: ''][4] ?? null;

        return $page && Route::has($page) ? route($page) : null;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // все MAX кнопок, одна без даты: большому блоку есть чем заполниться
        return [
            'color' => (string) ($settings['color'] ?? 'light-success'),
            'rows' => [
                ['key' => 'specs', 'label' => 'Конфигурации, сводная', 'icon' => 'fa-table-cells-large',
                    'url' => '#', 'date' => now()->subHours(3)->format('d.m.Y H:i')],
                ['key' => 'payments', 'label' => 'Договоры и оплаты', 'icon' => 'fa-money-check-dollar',
                    'url' => '#', 'date' => now()->subDays(2)->format('d.m.Y H:i')],
                ['key' => 'country_quarter', 'label' => 'Страны и статусы поквартально', 'icon' => 'fa-earth-europe',
                    'url' => '#', 'date' => now()->subDays(9)->format('d.m.Y H:i')],
                ['key' => 'industry', 'label' => 'Воронка: сферы и менеджеры', 'icon' => 'fa-industry',
                    'url' => '#', 'date' => null],
            ],
        ];
    }

    /**
     * Кнопки выгрузок и когда их последний раз делали
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['color', 'rows' => [['key', 'label', 'icon', 'url', 'date']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $reports = static::reports();
        $rows = [];

        foreach (static::chosen($settings) as $key) {
            [$label, $route, $prefix, $icon] = $reports[$key];

            $rows[] = [
                'key' => $key,
                'label' => $label,
                'icon' => $icon,
                'url' => route($route, ['mode' => 'excel']),
                'date' => !empty($settings['date']) ? static::lastExport($prefix) : null,
            ];
        }

        return ['color' => (string) $settings['color'], 'rows' => $rows];
    }

    /**
     * Выбранные отчёты: только известные и доступные, не больше MAX, порядок настройки
     *
     * @param array $settings
     * @return array ключи REPORTS
     */
    protected static function chosen(array $settings): array
    {
        $reports = static::reports();

        $keys = collect((array) ($settings['reports'] ?? []))
            ->filter(fn($key) => is_scalar($key))
            ->map(fn($key) => (string) $key)
            ->filter(fn($key) => isset($reports[$key]))
            ->unique()
            ->take(static::MAX)
            ->values()
            ->all();

        // пустая настройка — первая доступная выгрузка, чтобы кнопка не пропадала
        return $keys ?: array_slice(array_keys($reports), 0, 1);
    }

    /**
     * Выгрузки, которые сейчас объявлены маршрутами портала
     *
     * @return array ключ => описание из REPORTS
     */
    public static function reports(): array
    {
        return collect(static::REPORTS)
            ->filter(fn($report) => Route::has($report[1]))
            ->all();
    }

    /**
     * Когда выгрузку делали в последний раз: время самого свежего файла с таким именем
     *
     * @param string $prefix начало имени файла в storage/app/temp
     * @return string|null d.m.Y H:i, null — таких файлов ещё нет
     */
    protected static function lastExport(string $prefix): ?string
    {
        $files = glob(Storage::path('temp/' . $prefix . '*.xlsx')) ?: [];
        $time = 0;

        foreach ($files as $file) {
            $time = max($time, (int) @filemtime($file));
        }

        return $time > 0 ? Carbon::createFromTimestamp($time)->format('d.m.Y H:i') : null;
    }
}
