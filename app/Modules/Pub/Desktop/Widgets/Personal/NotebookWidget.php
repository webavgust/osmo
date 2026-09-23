<?php

namespace App\Modules\Pub\Desktop\Widgets\Personal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * Блокнот (patch v30): личные заметки пользователя — тот же блокнот, что в портале.
 *
 * Заметки берутся связью User::notes() (таблица user_notes), поэтому чужих заметок
 * виджет не покажет. Клик по заметке открывает сайдбар правки
 * (user-notes.sidebar_edit), ссылка внизу — сайдбар создания (user-notes.sidebar_add).
 *
 * Заметка — это и задача: флажок в строке ставит и снимает отметку «сделано»
 * (api.user-notes.done, колонка done_at). Выполненные всегда ниже невыполненных,
 * а показывать ли их — решает настройка «Выполненные задачи».
 */
class NotebookWidget extends Widget
{
    /** Порядок заметок */
    public const SORTS = [
        'favorite' => 'Избранные вверх',
        'date' => 'По дате',
    ];

    /** Что делать с выполненными задачами */
    public const DONE = [
        'day' => 'Показывать сутки, потом скрывать',
        'bottom' => 'Показывать внизу списка',
        'hide' => 'Сразу скрывать',
    ];

    public static function id(): string { return 'notebook'; }

    public static function name(): string { return 'Блокнот'; }

    public static function category(): string { return 'personal'; }

    public static function description(): string
    {
        return 'Личные заметки и задачи: отметка «сделано», избранные вверху, добавление и правка сайдбаром';
    }

    public static function icon(): string { return 'fa-note'; }

    public static function sizes(): array { return ['8x8', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 300; }

    public static function ttl(): int { return 60; }

    public static function fields(): array
    {
        return [
            ['key' => 'favorite_only', 'type' => 'bool', 'label' => 'Только избранные', 'default' => false],
            ['key' => 'sort', 'type' => 'select', 'label' => 'Сортировка', 'default' => 'favorite', 'options' => static::SORTS],
            ['key' => 'done', 'type' => 'select', 'label' => 'Выполненные задачи', 'default' => 'day', 'options' => static::DONE],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Количество', 'default' => 10, 'min' => 1, 'max' => 50],
        ];
    }

    /**
     * Строки: ['id', 'title', 'text', 'favorite', 'done', 'date', 'url', 'done_url'] плюс ссылка
     * добавления и число выполненных задач, скрытых настройкой (для пустого состояния)
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [...], 'add_url' => string|null, 'hidden_done' => int]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $add_url = route('user-notes.sidebar_add');
        $user = $ctx->user;
        if (!$user) {
            return ['rows' => [], 'add_url' => $add_url, 'hidden_done' => 0];
        }

        $done = $settings['done'] ?? 'day';
        $since = now()->subDay();

        $query = $user->notes();
        if ($settings['favorite_only']) $query->where('favorite', true);

        // сколько выполненных задач спрячет настройка: «все задачи выполнены» вместо «заметок нет»
        $hidden = match ($done) {
            'day' => (clone $query)->where('done_at', '<', $since)->count(),
            'hide' => (clone $query)->whereNotNull('done_at')->count(),
            default => 0,
        };

        // выполненные — сутки или сразу прочь; в скобках, чтобы не сломать «только избранные»
        if ($done === 'day') {
            $query->where(fn($q) => $q->whereNull('done_at')->orWhere('done_at', '>=', $since));
        } elseif ($done === 'hide') {
            $query->whereNull('done_at');
        }

        // невыполненные всегда выше выполненных, дальше прежний порядок
        $query->reorder()->orderByRaw('done_at is not null');
        if ($settings['sort'] === 'date') {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('favorite')->orderByDesc('created_at');
        }

        $notes = $query->limit((int) $settings['limit'])->get();

        $rows = [];
        foreach ($notes as $note) {
            $created = $note->created_at;
            $rows[] = [
                'id' => $note->id,
                'title' => Str::limit((string) $note->title, 70),
                'text' => static::plain((string) $note->text),
                'favorite' => (bool) $note->favorite,
                'done' => $note->isDone(),
                // год — только у заметок не этого года
                'date' => $created ? $created->format($created->isCurrentYear() ? 'd.m' : 'd.m.y') : '',
                'url' => route('user-notes.sidebar_edit', $note),
                'done_url' => route('api.user-notes.done', $note),
            ];
        }

        return ['rows' => $rows, 'add_url' => $add_url, 'hidden_done' => $hidden];
    }

    /**
     * Образцовые данные для превью
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        // столько заметок, сколько разрешено настройкой (до 40): высокий блок есть чем заполнить
        $pool = [
            ['Скидка по КП AA-794', 'Согласовать 12 % с руководителем'],
            ['Телефон бухгалтерии «Инфосистем»', '+7 495 000-00-00, Мария'],
            ['Проверить продление ключей ООО «Восток»', ''],
            ['Подготовить отчёт по воронке за квартал', 'Выгрузка по странам и сумма сделок'],
            ['Реквизиты для счёта «Русал»', 'ИНН и КПП взять из карточки компании'],
            ['Идеи к планёрке', 'Скоринг партнёров, новые скидки, обучение'],
            ['Список документов для тендера', ''],
            ['Созвон с Пекином', 'Разница +5 ч, звонить до 12:00 по Москве'],
        ];

        $n = min(40, max(1, (int) $settings['limit']));
        // последние две-три задачи выполнены — они и на столе идут в конце списка
        $done_from = $n - min(3, intdiv($n, 3));

        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            [$title, $text] = $pool[$i % count($pool)];
            $rows[] = [
                'id' => $i + 1, 'title' => $title, 'text' => $text, 'favorite' => $i < 2, 'done' => $i >= $done_from,
                'date' => now()->subDays($i * 3)->format('d.m'), 'url' => null, 'done_url' => null,
            ];
        }

        return ['rows' => $rows, 'add_url' => null, 'hidden_done' => 0];
    }

    /**
     * Текст заметки одной строкой для подсказки и второй колонки
     *
     * @param string $text
     * @return string
     */
    protected static function plain(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');

        return Str::limit($text, 140);
    }
}
