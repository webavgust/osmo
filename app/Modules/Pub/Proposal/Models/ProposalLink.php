<?php

namespace App\Modules\Pub\Proposal\Models;

use App\Models\Traits\HasLogger;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Связка КП «главное / второстепенное» (patch v33).
 *
 * Два КП логически объединяются, не склеиваясь: главное участвует во всех
 * расчётах, второстепенное — только просмотр и история. Связка живёт на группах
 * (proposals.group), а не на отдельных редакциях.
 *
 * Правила (проверяет ProposalLinkService): второстепенное бывает только у одного
 * главного (UNIQUE secondary_group), второстепенное не бывает главным для других,
 * у главного нет главного — цепочек нет. У главного может быть несколько второстепенных.
 */
class ProposalLink extends Model
{
    use HasLogger;

    protected $table = 'proposal_links';
    public $timestamps = false;

    protected $fillable = ['main_group', 'secondary_group', 'comment', 'linked_by', 'linked_at'];
    protected $casts = ['linked_at' => 'datetime'];

    /** Кэш групп второстепенных КП на запрос (см. secondaryGroups()) */
    protected static ?Collection $secondary_groups = null;

    /*** СВЯЗИ ***/

    /**
     * Главное КП — последняя редакция группы
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function main()
    {
        return $this->hasOne(Proposal::class, 'group', 'main_group')->ofMany('iteration', 'max');
    }

    /**
     * Второстепенное КП — последняя редакция группы
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function secondary()
    {
        return $this->hasOne(Proposal::class, 'group', 'secondary_group')->ofMany('iteration', 'max');
    }

    public function author()
    {
        // withTrashed: имя не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'linked_by')->withTrashed();
    }

    /*** ПОМОЩНИКИ ***/

    /**
     * Условие для сырого SQL: КП не второстепенное (участвует в расчётах).
     *
     * Пример: `->whereRaw(ProposalLink::notSecondarySql('p.group'))`.
     * Колонку передавать с таблицей: без обратных кавычек она оборачивается
     * грамматикой (`proposals`.`group`), с кавычками — подставляется как есть.
     *
     * @param string $groupColumn колонка группы КП во внешнем запросе
     * @return string
     */
    public static function notSecondarySql(string $groupColumn = 'proposals.group'): string
    {
        $column = str_contains($groupColumn, '`') ? $groupColumn : DB::getQueryGrammar()->wrap($groupColumn);

        return "NOT EXISTS (SELECT 1 FROM proposal_links pl_sec WHERE pl_sec.secondary_group = {$column})";
    }

    /**
     * Группы всех второстепенных КП (кэш на запрос; сбрасывается flush())
     *
     * @return Collection
     */
    public static function secondaryGroups(): Collection
    {
        if (static::$secondary_groups === null) {
            static::$secondary_groups = static::query()->pluck('secondary_group');
        }

        return static::$secondary_groups;
    }

    /**
     * Сбросить кэш secondaryGroups() — после любой правки связок
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$secondary_groups = null;
    }

    /**
     * Последняя редакция КП группы
     *
     * @param string|null $group
     * @return Proposal|null
     */
    public static function lastOf(?string $group): ?Proposal
    {
        if ($group === null || $group === '') return null;

        return Proposal::where('group', $group)->orderByDesc('iteration')->orderByDesc('id')->first();
    }

    /**
     * Короткая ссылка на КП для текстов: «№ AA793», без номера — «Название»
     *
     * @param Proposal|null $proposal
     * @return string пусто, если КП нет
     */
    public static function refOf(?Proposal $proposal): string
    {
        if (empty($proposal)) return '';

        $number = trim((string) $proposal->number);

        return $number !== '' ? '№ ' . $number : '«' . static::logText($proposal->name, 60) . '»';
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v33) ***/

    public static function logLabel(): string
    {
        return 'Связка КП';
    }

    /** Родитель — последняя редакция главного КП (связь по group, а не belongsTo по id) */
    public function logParent(): ?Model
    {
        return static::lastOf((string) $this->main_group);
    }

    public function logKey(int $index): string
    {
        return (string) $this->secondary_group;
    }

    /**
     * «Второстепенное КП № AK760 к № AA793»: добавленная/удалённая связка в ленте —
     * одна строка без полей, главное видно только из подписи (лента второстепенного)
     *
     * @param int|null $index
     * @return string
     */
    public function logTitle(?int $index = null): string
    {
        $ref = static::refOf(static::lastOf((string) $this->secondary_group));
        $main = static::refOf(static::lastOf((string) $this->main_group));

        return 'Второстепенное КП ' . ($ref !== '' ? $ref : $this->secondary_group) . ($main !== '' ? ' к ' . $main : '');
    }

    public static function logFields(): array
    {
        // вместо uuid группы — номер КП (последняя редакция)
        $proposal = function ($value) {
            $ref = static::refOf(static::lastOf((string) $value));

            return $ref !== '' ? 'КП ' . $ref : (string) $value;
        };

        return [
            'main_group' => ['label' => 'Главное КП', 'format' => $proposal],
            'secondary_group' => ['label' => 'Второстепенное КП', 'format' => $proposal],
            'comment' => ['label' => 'Комментарий'],
            'linked_at' => ['label' => 'Связано', 'type' => 'datetime'],
            'linked_by' => ['label' => 'Кто связал', 'relation' => 'author', 'title' => 'full_name'],
        ];
    }
}
