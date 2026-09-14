<?php

namespace App\Modules\Pub\DealProject\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Проект по сделкам (patch v24).
 *
 * В интерфейсе — «Проект». Класс зовётся DealProject, потому что модуль
 * Project уже занят «проектами компании» (конфигурации спецификаций).
 *
 * К проекту относятся сделки одного партнёра и одной компании, а также
 * спецификации: часть приезжает из КП этих сделок (from_proposal),
 * часть отмечается руками.
 */
class DealProject extends ModuleModel
{
    use HasLogger;

    protected $table = 'deal_projects';

    protected $fillable = [
        'partner_id', 'company_id', 'date_start', 'is_pilot', 'deadline',
        'comment', 'archived_at', 'archived_by', 'created_by',
    ];

    protected $casts = [
        'date_start' => 'date',
        'deadline' => 'date',
        'is_pilot' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /*** RELATIONS ***/

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** Привязки сделок Битрикса (сами сделки — в другой базе) */
    public function deals()
    {
        return $this->hasMany(DealProjectDeal::class, 'deal_project_id')->orderBy('crm_deal_id');
    }

    public function specifications()
    {
        return $this->hasMany(DealProjectSpecification::class, 'deal_project_id');
    }

    public function author()
    {
        // withTrashed: имя не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by')->withTrashed();
    }

    /*** SCOPES ***/

    /**
     * Только действующие проекты
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeActive(Builder $builder)
    {
        return $builder->whereNull('archived_at');
    }

    /**
     * Только архивные проекты
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeArchived(Builder $builder)
    {
        return $builder->whereNotNull('archived_at');
    }

    /*** ATTRIBUTES ***/

    /**
     * Проект в архиве
     *
     * @return bool
     */
    public function getIsArchivedAttribute(): bool
    {
        return !empty($this->archived_at);
    }

    /**
     * Id сделок Битрикса, прикреплённых к проекту
     *
     * @return array
     */
    public function dealIds(): array
    {
        return $this->relationLoaded('deals')
            ? $this->deals->pluck('crm_deal_id')->all()
            : $this->deals()->pluck('crm_deal_id')->all();
    }

    /**
     * Сами сделки Битрикса (отдельный запрос: другая база)
     *
     * @return Collection
     */
    public function crmDeals(): Collection
    {
        $ids = $this->dealIds();
        if (empty($ids)) return collect();

        return CrmDeal::whereIn('id', $ids)->orderByDesc('date_create')->get();
    }

    /**
     * Подпись проекта для плашек и заголовков попапов
     *
     * @return string
     */
    public function getLabelAttribute(): string
    {
        return 'Проект от ' . ($this->date_start?->format('d.m.Y') ?? '—')
            . ($this->is_pilot ? ' (пилот)' : '');
    }
    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v32) ***/

    public static function logLabel(): string
    {
        return 'Проект';
    }

    public function logTitle(?int $index = null): string
    {
        $partner = $this->partner_id ? static::logText($this->partner()->value('name'), 60) : '';

        return $this->label . ($partner !== '' ? ' · ' . $partner : '');
    }

    /**
     * Своей страницы у проекта нет: ведём в реестр сделок на вкладку проектов (или архива)
     * с поиском по первой сделке проекта
     *
     * @return string|null
     */
    public function logUrl(): ?string
    {
        $deal_id = $this->deals()->value('crm_deal_id');

        return route('crm-deal.index', array_filter([
            'mode' => $this->archived_at ? 'archive' : 'projects',
            'q' => $deal_id,
        ]));
    }

    /** Состояние на дату для проекта не строится: карточка проекта — попап, а не страница */
    public static function logStateView(): bool
    {
        return false;
    }

    public static function logChildren(): array
    {
        return [
            'deals' => DealProjectDeal::class,
            'specifications' => DealProjectSpecification::class,
        ];
    }

    public static function logIgnore(): array
    {
        return ['created_by'];
    }

    public static function logFields(): array
    {
        return [
            'partner_id' => ['label' => 'Партнёр', 'relation' => 'partner', 'title' => 'name'],
            'company_id' => ['label' => 'Компания', 'relation' => 'company', 'title' => 'name'],
            'date_start' => ['label' => 'Дата начала', 'type' => 'date'],
            'is_pilot' => ['label' => 'Пилот', 'type' => 'bool'],
            'deadline' => ['label' => 'Срок пилота', 'type' => 'date'],
            'comment' => ['label' => 'Комментарий'],
            'archived_at' => ['label' => 'В архиве с', 'type' => 'datetime'],
            'archived_by' => ['label' => 'Отправил в архив', 'relation' => 'archiver', 'title' => 'full_name'],
        ];
    }
}
