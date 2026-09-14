<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Str;

/**
 * Заметки по компаниям (patch v30): последние записи журнала переговоров
 * (модуль «Журналирование», таблица logs).
 *
 * Строки — дата, компания, КП и текст заметки; текст в базе хранится размеченным,
 * поэтому в виджете он очищается от тегов и обрезается. Клик по строке открывает
 * попап записи (log.box_detail), как на странице журнала. КП берётся по
 * proposal_group: показывается последняя редакция группы.
 */
class JournalWidget extends Widget
{
    /** До скольких символов режется текст заметки */
    public const TEXT_LIMIT = 160;

    public static function id(): string { return 'journal'; }

    public static function name(): string { return 'Заметки по компаниям'; }

    public static function category(): string { return 'common'; }

    public static function description(): string
    {
        return 'Последние записи журнала переговоров: дата, компания, КП и текст';
    }

    public static function icon(): string { return 'fa-note-sticky'; }

    public static function sizes(): array { return ['8x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 340; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'company', 'type' => 'entity', 'label' => 'Компания', 'entities' => ['company'],
                'default' => null, 'hint' => 'Пусто — записи по всем компаниям'],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько записей', 'default' => 10, 'min' => 3, 'max' => 50],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('log.all');
    }

    /**
     * Выбранная компания: id или null
     *
     * @param array $settings
     * @return int|null
     */
    protected static function companyId(array $settings): ?int
    {
        $company = $settings['company'] ?? null;
        $id = is_array($company) ? trim((string) ($company['id'] ?? '')) : '';

        return ctype_digit($id) ? (int) $id : null;
    }

    /**
     * Последние записи журнала
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'date', 'company', 'company_url', 'proposal', 'proposal_url', 'text', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $query = Log::query()->with('company:id,name')->orderByDesc('date')->orderByDesc('id');

        if ($id = static::companyId($settings)) {
            $query->where('company_id', $id);
        }

        $logs = $query->limit((int) $settings['limit'])->get();

        // КП по группе: последняя редакция (связь у Log отдаёт одну строку, а нам нужен номер свежей)
        $groups = $logs->pluck('proposal_group')->filter()->unique()->values()->all();
        $proposals = $groups
            ? Proposal::whereIn('group', $groups)->orderBy('iteration')->get(['id', 'group', 'number', 'name', 'iteration'])->keyBy('group')
            : collect();

        $rows = $logs->map(function (Log $log) use ($proposals) {
            $proposal = $log->proposal_group ? $proposals->get($log->proposal_group) : null;
            $number = $proposal ? trim((string) $proposal->number) : '';

            return [
                'id' => (int) $log->id,
                'date' => $log->date?->format('d.m.Y') ?? '—',
                'company' => (string) ($log->company?->name ?? '—'),
                'company_url' => $log->company ? route('company.detail', $log->company->id) : null,
                'proposal' => $proposal ? ($number !== '' ? '№ ' . $number : (string) $proposal->name) : '',
                'proposal_url' => $proposal ? route('proposal.detail', [$proposal->id, $proposal->iteration]) : null,
                'text' => static::plain($log->text),
                'url' => route('log.box_detail', $log->id),
            ];
        })->all();

        return ['rows' => $rows];
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
        $pool = [
            ['ООО «Альфа»', '№ AA-794', 'Созвон по продлению лицензий: ждут смету до конца недели, бюджет согласован, просят рассрочку на два платежа.'],
            ['АО «Вектор»', '№ AA-781', 'Договорились о демонстрации платформы для технического отдела, нужен стенд с интеграцией и тестовые учётные записи.'],
            ['ООО «Гранит»', '', 'Уточнили состав работ по внедрению, готовят внутреннее согласование с безопасностью и юристами.'],
            ['ГК Восток', '№ AA-762', 'Перенесли оплату второго этапа на следующий квартал из-за пересмотра бюджета.'],
            ['ООО «Северный терминал»', '№ AA-755', 'Запросили расширение на три площадки, отправили предварительный расчёт и сроки поставки оборудования.'],
            ['АО «Энергосбыт Регион»', '', 'Встреча с ИТ-директором: интересует миграция с текущей системы, ждут референсы по отрасли.'],
            ['ИП Смирнов', '№ AA-749', 'Клиент согласовал спецификацию, договор ушёл на подпись.'],
            ['ООО «Техноком»', '№ AA-741', 'Обсудили скидку партнёра, решение за руководителем направления после тендера.'],
            ['ПАО «Мостострой»', '', 'Отправили повторное письмо по продлению поддержки, ответа пока нет, напомнить через неделю.'],
            ['ООО «Медиа Плюс»', '№ AA-733', 'Провели пилот, замечания по отчётам закрыты, готовы переходить к закупке.'],
            ['АО «Логистик»', '№ AA-728', 'Смена контактного лица: новый менеджер проекта просит заново презентацию решения.'],
            ['ООО «Орбита»', '', 'Уточнили реквизиты для счёта и порядок закрывающих документов.'],
        ];

        // столько записей, сколько задано настройкой: высокий блок есть чем заполнить
        $rows = [];
        for ($i = 0, $n = max(1, (int) ($settings['limit'] ?? 10)); $i < $n; $i++) {
            [$company, $proposal, $text] = $pool[$i % count($pool)];

            $rows[] = [
                'id' => 0, 'date' => now()->subDays((int) round($i * 1.7))->format('d.m.Y'),
                'company' => $company, 'company_url' => null,
                'proposal' => $proposal, 'proposal_url' => null,
                'text' => $text, 'url' => null,
            ];
        }

        return ['rows' => $rows];
    }

    /**
     * Текст заметки без разметки, в одну строку
     *
     * @param string|null $html
     * @return string
     */
    protected static function plain(?string $html): string
    {
        $text = trim(preg_replace('~\s+~u', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', (string) $html))));

        return Str::limit(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), static::TEXT_LIMIT);
    }
}
