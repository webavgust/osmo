<?php

namespace App\Console\Commands;

use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Services\DesktopService;
use App\Modules\Pub\Desktop\Services\WidgetRegistry;
use App\Modules\Pub\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Стартовые системные рабочие столы (пресеты) — patch v30.
 *
 * Пять пресетов: «Руководитель» (по умолчанию: его копию получает новый пользователь),
 * «Менеджер продаж», «Финансы», «Ключи и продления», «Аналитик». Раскладка — в presets():
 * виджет, позиция и размер в ячейках сетки на 32 колонки, настройки блока.
 *
 * Перед записью каждый блок проверяется так же, как при сохранении стола
 * (DesktopService::save()): виджет есть в реестре и доступен автору, настройки проходят
 * normalize() без потери заданных ключей, обязательные заполнены, блок в сетке и не
 * пересекается с соседями, размер из sizes() виджета или у блока отжат замок (free).
 * Любая ошибка — не записывается ничего.
 *
 * Повторный запуск безопасен: пресет ищется среди системных по имени; если раскладка
 * или умолчания стола отличаются от заданных — сохраняется через DesktopService::save()
 * (version + 1, пользователям с копией предложат обновиться), иначе не трогается.
 *
 *   php artisan desktop:presets --dry-run   — проверить и показать раскладки, ничего не записывать
 *   php artisan desktop:presets --user=1    — автор пресетов (created_by / updated_by), администратор
 */
class DesktopPresetsCommand extends Command
{
    protected $signature = 'desktop:presets
        {--dry-run : всё проверить и показать раскладки, ничего не записывать}
        {--user= : id автора пресетов (администратор); по умолчанию первый с is_admin = 1}';

    protected $description = 'Собрать стартовые системные рабочие столы (пресеты)';

    /** Знаки блоков на схеме сетки */
    protected const MARKS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Проверить все пресеты и, если ошибок нет, записать их одной транзакцией
     *
     * @return int
     */
    public function handle(): int
    {
        $user = $this->author();
        if ($user === null) {
            return self::FAILURE;
        }

        // настройки виджетов нормализуются от имени автора — как при сохранении стола из браузера
        auth()->setUser($user);

        $dry = (bool) $this->option('dry-run');
        $context = DesktopContext::make(DesktopContext::DEFAULTS, $user)->toArray();

        $plans = [];
        foreach (static::presets() as $index => $preset) {
            $plan = $this->plan($preset, $index + 1, $user) + ['context' => $context];
            $plan['state'] = $this->state($plan);
            $this->report($plan, $dry);
            $plans[] = $plan;
        }

        $this->line('');

        if (collect($plans)->contains(fn($plan) => !empty($plan['errors']))) {
            $this->error('Есть ошибки — ничего не записано');

            return self::FAILURE;
        }

        foreach ($this->foreignDefaults($plans) as $desktop) {
            $this->warn(sprintf('Пресет «%s» (#%d) перестанет быть столом по умолчанию', $desktop->name, $desktop->id));
        }

        if ($dry) {
            $this->info('Пробный прогон: все проверки пройдены, ничего не записано');

            return self::SUCCESS;
        }

        try {
            $results = DB::transaction(fn() => array_map(fn($plan) => $this->write($plan, $user), $plans));
        } catch (\Throwable $e) {
            $this->error('Запись отменена: ' . $e->getMessage());

            return self::FAILURE;
        }

        foreach ($results as $line) {
            $this->info($line);
        }

        return self::SUCCESS;
    }

    /**
     * Автор пресетов: --user или первый администратор
     *
     * @return User|null null — не найден или не администратор (сообщение уже выведено)
     */
    protected function author(): ?User
    {
        $id = $this->option('user');
        $user = $id !== null && $id !== ''
            ? User::find((int) $id)
            : User::where('is_admin', 1)->orderBy('id')->first();

        if ($user === null) {
            $this->error($id !== null && $id !== '' ? "Пользователь #{$id} не найден" : 'Нет пользователя с is_admin = 1');

            return null;
        }

        if (!$user->isPanelAdmin()) {
            $this->error("Системные пресеты меняет только администратор, а у пользователя #{$user->id} нет is_admin");

            return null;
        }

        return $user;
    }

    /*** ПРЕСЕТЫ ***/

    /**
     * Пресеты в порядке sort: имя, назначение, флаг «по умолчанию», блоки.
     *
     * Блок — block(виджет, 'x,y', 'ШxВ', настройки, free): координаты с 0, размер в
     * ячейках сетки на 32 колонки; free — размера нет в sizes() виджета, замок отжат.
     *
     * @return array [['name', 'about', 'default', 'blocks' => [...]], …]
     */
    public static function presets(): array
    {
        return [
            [
                'name' => 'Руководитель',
                'about' => 'Деньги, воронка, ключи — всё на одном экране без прокрутки',
                'default' => true,
                'blocks' => [
                    static::block('banner', '0,0', '32x2', [
                        'headline' => 'Рабочий стол руководителя',
                        'text' => 'Деньги, воронка и ключи на одном экране. Состав меняется кнопкой «Редактировать».',
                        'button_label' => 'Воронка продаж',
                        'button_url' => '/bitrix/dashboard',
                    ]),
                    static::block('kpi', '0,2', '4x2', ['metric' => 'funnel.sales']),
                    static::block('kpi', '4,2', '4x2', ['metric' => 'proposals.in_work_sum']),
                    static::block('payments_fact', '8,2', '4x2', ['range' => 'days', 'days' => 30]),
                    static::block('keys_expiring', '12,2', '4x2', ['days' => 30]),
                    static::block('currency', '16,2', '4x2'),
                    static::block('period', '20,2', '4x2'),
                    static::block('sync', '24,2', '4x2'),
                    static::block('team', '28,2', '4x2'),
                    static::block('funnel_stages', '0,4', '8x4'),
                    static::block('proposal_status', '0,8', '8x4'),
                    static::block('country_month', '8,4', '16x8'),
                    static::block('scoring_top', '24,4', '8x8'),
                ],
            ],
            [
                'name' => 'Менеджер продаж',
                'about' => 'Свои КП, что без сделки, ближайшие напоминания',
                'default' => false,
                'blocks' => [
                    static::block('heading', '0,0', '32x1', ['text' => 'Мои КП']),
                    static::block('proposals_mine', '0,1', '8x8'),
                    static::block('reminders', '8,1', '8x4'),
                    static::block('proposals_nodeal', '8,5', '8x4', ['mine' => true]),
                    static::block('deals_top', '16,1', '8x8'),
                    // закладки на страницы портала: «page:имя маршрута|подпись»
                    static::block('links', '24,1', '8x4', ['items' => [
                        'page:proposal.index|КП',
                        'page:external_proposal.index|Внешние КП',
                        'page:crm-deal.index|Реестр сделок',
                        'page:dashboard.index|Воронка',
                        'page:payment_calendar.index|Платёжный календарь',
                        'page:analytics.partners|Скоринг партнёров',
                    ]]),
                    static::block('external_proposals', '24,5', '8x4'),
                    static::block('proposal_status', '0,9', '8x2', ['mine' => true]),
                    static::block('button', '8,9', '4x2', ['action' => 'proposal_create']),
                    static::block('countdown', '12,9', '4x2', ['target' => 'quarter']),
                    static::block('calendar', '16,9', '8x4', [], true),
                    static::block('notebook', '24,9', '8x4'),
                ],
            ],
            [
                'name' => 'Финансы',
                'about' => 'Договоры и оплаты: факт, план, просрочка, сверка, календарь на год',
                'default' => false,
                'blocks' => [
                    static::block('payments_fact', '0,0', '4x2', ['range' => 'days', 'days' => 30]),
                    // горизонта в днях у плана нет — ближайший период «Текущий месяц»
                    static::block('payments_plan', '4,0', '4x2', ['period' => 'month']),
                    static::block('payments_overdue', '8,0', '4x2'),
                    static::block('currency', '12,0', '4x2'),
                    static::block('period', '16,0', '4x2'),
                    static::block('rates', '20,0', '4x2'),
                    static::block('report_download', '24,0', '4x2', ['reports' => ['payments']]),
                    static::block('specs_status', '28,0', '4x2'),
                    static::block('payment_calendar', '0,2', '16x8', ['days' => 365]),
                    static::block('payments_overdue', '16,2', '8x4'),
                    static::block('specs_reconcile', '16,6', '8x4'),
                    static::block('payments_by_partner', '24,2', '8x8'),
                    static::block('contracts_unsigned', '0,10', '8x3', [], true),
                    static::block('payment_summary', '8,10', '8x3', [], true),
                    // не «Журнал изменений»: он только с правом entity_log_view, у остальных на его месте дыра
                    static::block('chart', '16,10', '16x3', ['metric' => 'payments.fact_sum', 'step' => 'month', 'title' => 'Оплаты по месяцам'], true),
                ],
            ],
            [
                'name' => 'Ключи и продления',
                'about' => 'Три горизонта истечения, суммы продлений, реестр целиком',
                'default' => false,
                'blocks' => [
                    static::block('keys_expiring', '0,0', '4x2', ['days' => 30, 'title' => '30 дней']),
                    static::block('keys_expiring', '4,0', '4x2', ['days' => 60, 'title' => '60 дней']),
                    static::block('keys_expiring', '8,0', '4x2', ['days' => 90, 'title' => '90 дней']),
                    static::block('renewals', '12,0', '8x4'),
                    static::block('currency', '20,0', '4x2'),
                    // выгрузки по ключам нет — ближайшая: сводная по конфигурациям спецификаций
                    static::block('report_download', '24,0', '4x2', ['reports' => ['specs']]),
                    static::block('clock', '28,0', '4x2', ['zone_1' => 'Asia/Shanghai', 'zone_2' => 'Europe/Moscow']),
                    static::block('keys_by_company', '0,2', '8x4'),
                    // вместо двух «Карточек ключа» (им нужен конкретный ключ): сумма продлений и ссылка на реестр
                    static::block('kpi', '8,2', '4x2', ['metric' => 'keys.renewal_90']),
                    static::block('button', '8,4', '4x2', ['action' => 'custom', 'url' => '/analytics/licenses', 'label' => 'Реестр лицензий']),
                    static::block('reminders', '20,2', '8x4'),
                    static::block('china', '28,2', '4x2'),
                    static::block('license_registry', '0,6', '32x7', [], true),
                ],
            ],
            [
                'name' => 'Аналитик',
                'about' => 'Сравнения периодов, графики показателей, воронка, скидки, причины проигрыша',
                'default' => false,
                'blocks' => [
                    static::block('period', '0,0', '4x2'),
                    static::block('currency', '4,0', '4x2'),
                    // «Воронка · Сумма сделок» без периода — сравнивается выигранное в КП
                    static::block('compare', '8,0', '8x4', ['metric' => 'proposals.won_sum', 'period_a' => 'quarter', 'period_b' => 'prev_quarter']),
                    static::block('chart', '16,0', '16x8', ['metric' => 'payments.fact_sum', 'step' => 'month']),
                    static::block('proposal_conversion', '0,2', '4x2'),
                    // цели (плана) в портале нет — число цели оставлено по умолчанию, блок просит задать цель
                    static::block('progress', '4,2', '4x2', ['metric' => 'proposals.won_sum']),
                    static::block('funnel_table', '0,4', '16x8'),
                    static::block('discounts', '16,8', '8x4'),
                    static::block('lost_reasons', '24,8', '8x4'),
                    static::block('scenarios_top', '16,12', '8x6', [], true),
                    static::block('industry', '24,12', '8x6', [], true),
                ],
            ],
        ];
    }

    /**
     * Блок пресета
     *
     * @param string $widget id виджета
     * @param string $at 'x,y' — колонка и строка с 0
     * @param string $size 'ШxВ' в ячейках
     * @param array $settings заданные настройки (остальные — по умолчанию виджета)
     * @param bool $free размер не из sizes() виджета — замок отжат
     * @return array ['widget', 'x', 'y', 'w', 'h', 'settings', 'free']
     */
    protected static function block(string $widget, string $at, string $size, array $settings = [], bool $free = false): array
    {
        [$x, $y] = array_map('intval', explode(',', $at) + [0, 0]);
        [$w, $h] = array_map('intval', explode('x', $size) + [0, 0]);

        return compact('widget', 'x', 'y', 'w', 'h', 'settings', 'free');
    }

    /*** ПРОВЕРКА ***/

    /**
     * Проверить пресет и собрать элементы раскладки для DesktopService::save()
     *
     * @param array $preset элемент presets()
     * @param int $sort порядок пресета
     * @param User $user автор
     * @return array ['name', 'about', 'default', 'sort', 'blocks', 'items', 'cells', 'desktop', 'errors', 'warnings']
     */
    protected function plan(array $preset, int $sort, User $user): array
    {
        $columns = (int) config('desktop.columns', 32);
        $items = $cells = $errors = $warnings = $counts = $pairs = [];

        foreach ($preset['blocks'] as $index => $block) {
            ['widget' => $id, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'settings' => $settings, 'free' => $free] = $block;
            $mark = self::MARKS[$index] ?? '?';
            $label = sprintf('%s %s %d,%d', $mark, $id, $x, $y);

            // позиция и занятость сетки
            if ($w < 1 || $h < 1 || $h > DesktopService::MAX_ROWS) {
                $errors[] = "{$label}: размер {$w}x{$h} вне допустимого";
            }
            if ($x < 0 || $y < 0 || $x + $w > $columns) {
                $errors[] = "{$label}: блок выходит за сетку в {$columns} колонок (x + w = " . ($x + $w) . ')';
            }
            for ($cy = $y; $cy < $y + max(1, $h); $cy++) {
                for ($cx = max(0, $x); $cx < min($columns, $x + max(1, $w)); $cx++) {
                    if (isset($cells[$cy][$cx])) {
                        $other = $cells[$cy][$cx];
                        if ($other !== '#' && !isset($pairs[$other . $mark])) {
                            $pairs[$other . $mark] = true;
                            $errors[] = "{$label}: пересекается с блоком {$other}";
                        }
                        $cells[$cy][$cx] = '#';
                    } else {
                        $cells[$cy][$cx] = $mark;
                    }
                }
            }

            $class = WidgetRegistry::find($id);
            if ($class === null) {
                $errors[] = "{$label}: виджета «{$id}» нет в реестре";
                continue;
            }
            if (!$class::available($user)) {
                $errors[] = "{$label}: виджет недоступен автору #{$user->id} — save() его отбросит";
            }

            // uid постоянный: id виджета и номер его вхождения в пресет
            $counts[$id] = ($counts[$id] ?? 0) + 1;
            $uid = $id . '-' . $counts[$id];
            if (!preg_match(DesktopService::UID_PATTERN, $uid)) {
                $errors[] = "{$label}: uid «{$uid}» не проходит DesktopService::UID_PATTERN";
            }

            // размер: из sizes() виджета, иначе нужен отжатый замок
            $allowed = $class::allows($w, $h);
            if (!$allowed && !$free) {
                $errors[] = "{$label}: размера {$w}x{$h} нет в sizes() (" . implode(', ', $class::sizes()) . ') — нужен free';
            } elseif ($allowed && $free) {
                $warnings[] = "{$label}: размер {$w}x{$h} есть в sizes(), свободный размер не нужен";
            }

            // настройки: normalize() не теряет и не меняет ни одного заданного ключа
            $normalized = $class::normalize($settings);
            foreach ($settings as $key => $value) {
                if (!array_key_exists($key, $normalized)) {
                    $errors[] = "{$label}: настройки «{$key}» нет в схеме виджета";
                } elseif (static::canon($normalized[$key]) !== static::canon($value)) {
                    $errors[] = "{$label}: настройка «{$key}» — задано " . static::canon($value) . ', normalize() дал ' . static::canon($normalized[$key]);
                }
            }
            foreach ($class::schema() as $field) {
                if (!empty($field['required']) && blank($normalized[$field['key']] ?? null)) {
                    $errors[] = "{$label}: обязательная настройка «{$field['key']}» не заполнена";
                }
            }
            foreach (static::linkProblems($settings) as $problem) {
                $errors[] = "{$label}: {$problem}";
            }

            $items[] = [
                'uid' => $uid, 'widget' => $id, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
                'free_size' => $free, 'settings' => $settings,
            ];
        }

        $found = Desktop::system()->where('name', $preset['name'])->orderBy('id')->get();
        if ($found->count() > 1) {
            $warnings[] = sprintf('системных пресетов с этим именем %d — обновляется первый (#%d)', $found->count(), $found->first()->id);
        }

        return [
            'name' => $preset['name'],
            'about' => $preset['about'],
            'default' => (bool) $preset['default'],
            'sort' => $sort,
            'blocks' => $preset['blocks'],
            'items' => $items,
            'cells' => $cells,
            'desktop' => $found->first(),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Ссылки в настройках ведут на страницы портала: адрес «/…» — на существующий
     * маршрут, пункт набора ссылок «page:имя» — на маршрут без обязательных параметров
     *
     * @param array $settings
     * @return array описания проблем
     */
    protected static function linkProblems(array $settings): array
    {
        $out = [];

        foreach (['button_url', 'url'] as $key) {
            $url = $settings[$key] ?? null;
            if (is_string($url) && str_starts_with($url, '/') && !static::routeExists($url)) {
                $out[] = "адрес {$url} не ведёт ни на одну страницу портала";
            }
        }

        foreach ((array) ($settings['items'] ?? []) as $item) {
            if (!is_string($item) || !preg_match('~^page:\s*([^|]+)~', $item, $match)) continue;

            $name = trim($match[1]);
            try {
                $ok = Route::has($name) && route($name) !== '';
            } catch (\Throwable) {
                $ok = false;
            }
            if (!$ok) {
                $out[] = "маршрута {$name} нет или ему нужны параметры";
            }
        }

        return $out;
    }

    /**
     * Есть ли GET-маршрут портала для пути
     *
     * @param string $path
     * @return bool
     */
    protected static function routeExists(string $path): bool
    {
        try {
            app('router')->getRoutes()->match(Request::create($path, 'GET'));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Значение для сравнения: ключи объектов по алфавиту (MySQL хранит JSON со своим
     * порядком ключей), списки как есть
     *
     * @param mixed $value
     * @return string
     */
    protected static function canon($value): string
    {
        $sort = function ($value) use (&$sort) {
            if (!is_array($value)) return $value;
            if (!array_is_list($value)) ksort($value);

            return array_map($sort, $value);
        };

        return json_encode($sort($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /*** ЗАПИСЬ ***/

    /**
     * Что будет с пресетом: new — создать, changed — раскладка или умолчания стола
     * отличаются, same — совпадает
     *
     * @param array $plan
     * @return string
     */
    protected function state(array $plan): string
    {
        if ($plan['desktop'] === null) {
            return 'new';
        }

        return $this->sameLayout($plan['desktop'], $plan['items'])
            && static::canon((array) $plan['desktop']->context) === static::canon($plan['context'])
                ? 'same' : 'changed';
    }

    /**
     * Раскладка стола совпадает с заданной: те же uid, виджеты, позиции, размеры, замки и
     * настройки. Настройки сравниваются после normalize() с обеих сторон — новое поле
     * виджета со значением по умолчанию составом не считается
     *
     * @param Desktop $desktop
     * @param array $items элементы раскладки из plan()
     * @return bool
     */
    protected function sameLayout(Desktop $desktop, array $items): bool
    {
        $stored = $desktop->widgets()->get()->keyBy('uid');
        if ($stored->count() !== count($items)) {
            return false;
        }

        foreach ($items as $item) {
            $row = $stored->get($item['uid']);
            $class = WidgetRegistry::find($item['widget']);

            if ($row === null || $class === null || $row->widget !== $item['widget']) {
                return false;
            }
            if ([$row->x, $row->y, $row->w, $row->h, (bool) $row->free_size] !== [$item['x'], $item['y'], $item['w'], $item['h'], (bool) $item['free_size']]) {
                return false;
            }
            if (static::canon($class::normalize((array) $row->settings)) !== static::canon($class::normalize($item['settings']))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Записать пресет: создать или сохранить раскладку (если состав поменялся), выставить
     * порядок и флаг «по умолчанию»
     *
     * @param array $plan
     * @param User $user
     * @return string строка итога
     * @throws \RuntimeException раскладка после сохранения не совпала — транзакция откатывается
     */
    protected function write(array $plan, User $user): string
    {
        $desktop = $plan['desktop'] ?? DesktopService::create($user, $plan['name'], true);
        $before = (int) $desktop->version;

        if ($plan['state'] !== 'same') {
            // save() проверяет элементы (cleanItems) и у системного стола поднимает version
            $desktop = DesktopService::save($desktop, $plan['items'], $user, $plan['context']);

            if ($plan['state'] === 'new') {
                $desktop->update(['version' => 1]);
            }

            if (!$this->sameLayout($desktop, $plan['items'])) {
                throw new \RuntimeException("«{$plan['name']}»: после сохранения раскладка не совпала с заданной");
            }
        }

        if ((int) $desktop->sort !== $plan['sort']) {
            $desktop->update(['sort' => $plan['sort']]);
        }

        if ($plan['default']) {
            $others = Desktop::system()->where('id', '!=', $desktop->id)->where('is_default', true)->exists();
            if (!$desktop->is_default || $others) {
                DesktopService::makeDefault($desktop, $user);
            }
        } elseif ($desktop->is_default) {
            $desktop->update(['is_default' => false]);
        }

        $count = count($plan['items']);

        return match ($plan['state']) {
            'new' => sprintf('«%s»: создан (#%d, версия 1, блоков %d)', $plan['name'], $desktop->id, $count),
            'changed' => sprintf('«%s»: обновлён (#%d, версия %d → %d, блоков %d)', $plan['name'], $desktop->id, $before, $desktop->version, $count),
            default => sprintf('«%s»: без изменений (#%d, версия %d)', $plan['name'], $desktop->id, $desktop->version),
        };
    }

    /**
     * Системные пресеты, с которых снимется флаг «по умолчанию»
     *
     * @param array $plans
     * @return \Illuminate\Support\Collection<Desktop>
     */
    protected function foreignDefaults(array $plans)
    {
        $default = collect($plans)->firstWhere('default', true);

        return Desktop::system()->where('is_default', true)
            ->when($default['desktop'] ?? null, fn($query, $desktop) => $query->where('id', '!=', $desktop->id))
            ->orderBy('id')
            ->get();
    }

    /*** ВЫВОД ***/

    /**
     * Показать пресет: что с ним будет, ошибки и предупреждения; в пробном прогоне —
     * таблица блоков и схема занятости сетки
     *
     * @param array $plan
     * @param bool $dry
     * @return void
     */
    protected function report(array $plan, bool $dry): void
    {
        $version = (int) ($plan['desktop']->version ?? 0);
        $state = match ($plan['state']) {
            'new' => 'будет создан',
            'changed' => sprintf('будет обновлён: #%d, версия %d → %d', $plan['desktop']->id, $version, $version + 1),
            default => sprintf('без изменений: #%d, версия %d', $plan['desktop']->id, $version),
        };

        $this->line('');
        $this->line(sprintf('<options=bold>%d. %s</>%s — %s', $plan['sort'], $plan['name'], $plan['default'] ? ' (по умолчанию)' : '', $plan['about']));
        $this->line('   ' . $state);

        if ($dry) {
            $this->table(['', 'Виджет', 'x,y', 'w×h', 'free', 'Настройки'], array_map(fn($block, $index) => [
                self::MARKS[$index] ?? '?',
                $block['widget'],
                $block['x'] . ',' . $block['y'],
                $block['w'] . '×' . $block['h'],
                $block['free'] ? 'да' : '',
                static::describe($block['settings']),
            ], $plan['blocks'], array_keys($plan['blocks'])));

            $this->grid($plan['cells']);
        }

        foreach ($plan['warnings'] as $warning) {
            $this->warn('   ' . $warning);
        }
        foreach ($plan['errors'] as $error) {
            $this->error('   ' . $error);
        }

        if (empty($plan['errors'])) {
            $free = collect($plan['blocks'])->where('free', true)->map(fn($block) => $block['widget'] . ' ' . $block['w'] . 'x' . $block['h']);
            $this->info(sprintf('   Проверки пройдены: блоков %d%s', count($plan['items']), $free->isEmpty() ? '' : ', свободный размер — ' . $free->implode(', ')));
        }
    }

    /**
     * Схема занятости сетки: знак блока, «.» — свободно, «#» — пересечение
     *
     * @param array $cells [y][x] => знак
     * @return void
     */
    protected function grid(array $cells): void
    {
        $columns = (int) config('desktop.columns', 32);
        $height = empty($cells) ? 0 : max(array_keys($cells)) + 1;

        $this->line('    ' . implode('', array_map(fn($x) => $x % 10, range(0, $columns - 1))));
        for ($y = 0; $y < $height; $y++) {
            $row = '';
            for ($x = 0; $x < $columns; $x++) {
                $row .= $cells[$y][$x] ?? '.';
            }
            $this->line(sprintf('%3d %s', $y, $row));
        }
    }

    /**
     * Заданные настройки одной строкой: key=value; …
     *
     * @param array $settings
     * @return string
     */
    protected static function describe(array $settings): string
    {
        $text = collect($settings)
            ->map(fn($value, $key) => $key . '=' . (is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)))
            ->implode('; ');

        return mb_strimwidth($text, 0, 110, '…');
    }
}
