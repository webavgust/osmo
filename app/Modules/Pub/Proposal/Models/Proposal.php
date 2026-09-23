<?php

namespace App\Modules\Pub\Proposal\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Client\Services\ClientService;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\ProposalPdfTemplate\Models\ProposalPdfTemplate;
use App\Modules\Pub\ProposalPlatform\Models\ProposalPlatform;
use App\Modules\Pub\ProposalSoftware\Models\ProposalSoftware;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalWork\Models\ProposalWork;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Proposal extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['group', 'iteration', 'name', 'name_alt', 'sended_at', 'rate_unlimited', 'number', 'number_int', 'currency_rate', 'currency_rate_cumulative', 'lang', 'nds', 'status', 'status_reason', 'status_comment', 'crm_deal_id'];
    protected $searchable = ["name", "number"];
    protected $casts = ['sended_at' => 'date', 'status_changed_at' => 'datetime', 'crm_deal_linked_at' => 'datetime'];

    /**
     * Дополняем слушатели событий
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($instance) {
            // почистим variants
            $instance->variants->each(function($sub_instance) {
                $sub_instance->delete();
            });
            $instance->works->each(function($sub_instance) {
                $sub_instance->delete();
            });
            $instance->software->each(function($sub_instance) {
                $sub_instance->delete();
            });
        });
    }
    public function getRouteKey()
    {
        return $this->group;
    }
    public function getRouteKeyName()
    {
        return 'group';
    }



    /*** RELATIONS ***/

    public function manager()
    {
        // withTrashed: имя менеджера не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'manager_id')->withTrashed();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function variants()
    {
        return $this->hasMany(ProposalVariant::class)->orderBy('is_main', 'desc')->orderBy('id');
    }

    public function last_variant()
    {
        return $this->variants()->take(1);
    }



    public function platforms()
    {
        return $this->hasMany(ProposalPlatform::class)->orderBy('sort');
    }

    public function software()
    {
        return $this->hasMany(ProposalSoftware::class)->orderBy('sort');
    }

    public function works()
    {
        return $this->hasMany(ProposalWork::class)->orderBy('sort');
    }


        public function logs()
    {
        return $this->hasMany(Log::class, 'proposal_group', 'group')->orderBy('date', 'desc')->orderBy('id', 'desc');
    }


    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_slug', 'slug');
    }

    public function proposal_pdf_templates()
    {
        return $this->hasMany(ProposalPdfTemplate::class);
    }

    public function proposal_parent()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Сделка Битрикса, к которой привязано КП (связь 1:1).
     * Живёт в отдельной БД — соединение bitrix.
     */
    public function crm_deal()
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id', 'id');
    }

    /**
     * Привязки сделок Битрикса (patch v29): живут на группе, общие для всех редакций
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function crm_deal_links()
    {
        return $this->hasMany(ProposalCrmDeal::class, 'proposal_group', 'group')->orderByDesc('is_main')->orderBy('id');
    }

    /** Кто последним менял статус */
    public function status_author()
    {
        return $this->belongsTo(User::class, 'status_changed_by')->withTrashed();
    }

    /**
     * Запись внешней системы (OSMOVIEW CP), из которой перенесено КП (patch v21).
     * Связь по group — относится ко всем итерациям.
     */
    public function external()
    {
        return $this->hasOne(\App\Modules\Pub\ExternalProposal\Models\ExternalProposal::class, 'proposal_group', 'group');
    }

    /**
     * Связка с главным КП (patch v33): есть — это КП второстепенное
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function main_link()
    {
        return $this->hasOne(ProposalLink::class, 'secondary_group', 'group');
    }

    /**
     * Та же связка с главным, но коллекцией (0..1 строк) — только для журнала:
     * гидрация слепка раздаёт детей коллекциями
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function main_links()
    {
        return $this->hasMany(ProposalLink::class, 'secondary_group', 'group');
    }

    /**
     * Связки со второстепенными КП (patch v33): есть — это КП главное
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function secondary_links()
    {
        return $this->hasMany(ProposalLink::class, 'main_group', 'group')->orderBy('id');
    }


    /**
     * Scope search для поиска
     *
     * @param Builder $builder
     * @param $search
     * @return Builder
     */
    public function scopeSearch(Builder $builder, $search)
    {
        $words = collect(explode(" ", $search));
        // patch v33: сценарий, компания и партнёр — подзапросами IN, а не whereHas:
        // коррелированный EXISTS по вариантам и сценариям шёл ~0,8 с на каждый запрос списка
        // (у proposal_variant_scenarios нет индекса по варианту), IN MySQL считает один раз
        $like = '%' . $search . '%';
        $builder->where(function ($builder) use ($words) {
            $builder->where(function ($builder) use ($words) {
                foreach ($this->searchable as $i => $field) {
                    $builder->orWhere(function ($builder) use ($words, $field) {
                        $words->each(fn($item) => $builder->where($field, 'LIKE', '%' . $item . '%'));
                    });
                }
            });
        })->orWhereIn('proposals.id', function ($query) use ($like) {
            $query->select('pv.proposal_id')
                ->from('proposal_variants as pv')
                ->join('proposal_variant_scenarios as pvs', 'pvs.proposal_variant_id', '=', 'pv.id')
                ->join('scenarios as s', 's.id', '=', 'pvs.scenario_id')
                ->where('s.name', 'like', $like);
        })->orWhereIn('proposals.company_id', function ($query) use ($like) {
            $query->select('id')->from('companies')->where('name', 'like', $like);
        })->orWhereIn('proposals.partner_id', function ($query) use ($like) {
            $query->select('id')->from('partners')->where('name', 'like', $like);
        });

        return $builder;
    }

    /**
     * Scope по статусу: Proposal::status('won')->get()
     *
     * @param Builder $builder
     * @param string|array $status
     * @return Builder
     */
    public function scopeStatus(Builder $builder, string|array $status)
    {
        return $builder->whereIn('status', (array) $status);
    }

    /**
     * Scope: только последняя итерация каждого КП
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeLatestIteration(Builder $builder)
    {
        return $builder->whereIn('id', function ($query) {
            $query->selectRaw('MAX(id)')->from('proposals')->groupBy('group');
        });
    }

    /**
     * Scope: только КП, участвующие в расчётах, — без второстепенных (patch v33).
     * Колонка группы квалифицирована таблицей: scope работает и в запросах с join
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeCounted(Builder $builder)
    {
        return $builder->whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('proposal_links as pl_sec')
                ->whereColumn('pl_sec.secondary_group', 'proposals.group');
        });
    }

    /**
     * КП второстепенное к другому (patch v33): только просмотр, в расчётах не участвует.
     * Повторное обращение запроса не делает — связь остаётся загруженной
     *
     * @return bool
     */
    public function getIsSecondaryAttribute(): bool
    {
        // модель из слепка журнала: связка лежит коллекцией
        if (!$this->relationLoaded('main_link') && $this->relationLoaded('main_links')) {
            return $this->main_links->isNotEmpty();
        }

        return $this->main_link !== null;
    }

    /**
     * У КП есть второстепенные (patch v33)
     *
     * @return bool
     */
    public function getIsMainAttribute(): bool
    {
        return $this->secondary_links->isNotEmpty();
    }

    public function getCostTotalAttribute()
    {
        return $this->variants?->last()?->cost_total ?? 0;
    }


    public function getHasEmptyScenariosAttribute()
    {
        return $this->variants()->whereHas('proposal_scenarios', function($builder) {
            $builder->whereHas('scenario', function($builder) {
                $builder->whereDoesntHave('neuroservices');
            });
        })->count() > 0;
    }

    public function getNameNumberAttribute()
    {
        $ret = [];
        if(!empty($this->number))
            $ret[] = "[{$this->number}]";
        $ret[] = $this->name;

        return implode(" ", $ret);
    }

    public function getIsForeignCurrencyAttribute()
    {
        return $this->currency?->slug !== Currency::CURRENCY_DEFAULT;
    }

    /**
     * Статус в виде enum
     *
     * @return ProposalStatus
     */
    public function getStatusEnumAttribute(): ProposalStatus
    {
        return ProposalStatus::tryFrom((string) $this->status) ?? ProposalStatus::IN_WORK;
    }

    /**
     * Оформление статуса: label, color, icon
     *
     * @return array
     */
    public function getStatusDecorateAttribute(): array
    {
        return $this->status_enum->data();
    }

    /**
     * Причина проигрыша в виде enum
     *
     * @return ProposalLostReason|null
     */
    public function getReasonEnumAttribute(): ?ProposalLostReason
    {
        return ProposalLostReason::tryFrom((string) $this->status_reason);
    }

    /**
     * Оформление причины
     *
     * @return array|null
     */
    public function getReasonDecorateAttribute(): ?array
    {
        return $this->reason_enum?->data();
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    /** Слаг ленты (совпадает с config/entity_log.php) */
    public static function logType(): string
    {
        return 'proposal';
    }

    public static function logLabel(): string
    {
        return 'КП';
    }

    /** Лента общая для всех редакций — ключ group */
    public function logGroupKey(): string
    {
        return (string) $this->group;
    }

    /** Корень по ключу ленты — последняя редакция группы */
    public static function logFindByGroupKey(string $key): ?\Illuminate\Database\Eloquent\Model
    {
        return static::where('group', $key)->orderByDesc('iteration')->orderByDesc('id')->first();
    }

    /** Предыдущая редакция: дифф новой редакции считается относительно неё */
    public function logPredecessor(): ?\Illuminate\Database\Eloquent\Model
    {
        if ((int) $this->iteration <= 1) return null;

        return static::where('group', $this->group)
            ->where('iteration', '<', (int) $this->iteration)
            ->orderByDesc('iteration')
            ->orderByDesc('id')
            ->first();
    }

    public function logTitle(?int $index = null): string
    {
        $number = trim((string) $this->number);
        $title = $number !== '' ? 'КП № ' . $number : 'КП «' . static::logText($this->name, 60) . '»';

        return $title . ' (ред. ' . (int) $this->iteration . ')';
    }

    public function logUrl(): ?string
    {
        return route('proposal.detail', [$this, $this->iteration]);
    }

    public static function logChildren(): array
    {
        return [
            'variants' => ProposalVariant::class,
            'software' => ProposalSoftware::class,
            'works' => ProposalWork::class,
            'crm_deal_links' => ProposalCrmDeal::class,
            // patch v33: связка «главное / второстепенное» — видна в лентах обоих КП
            'secondary_links' => ProposalLink::class,
            'main_links' => ProposalLink::class,
        ];
    }

    /** Статус и сделка пишутся во все редакции группы */
    public static function logSharedFields(): array
    {
        return ['status', 'status_reason', 'status_comment', 'crm_deal_id'];
    }

    public static function logIgnore(): array
    {
        return [
            'group', 'iteration', 'number_int', 'rate_unlimited', 'currency_rate_cumulative', 'neuro_costs',
            'status_changed_at', 'status_changed_by', 'crm_deal_linked_at', 'crm_deal_linked_by', 'proposal_parent_id',
        ];
    }

    public static function logFields(): array
    {
        return [
            'number' => ['label' => 'Номер КП'],
            'name' => ['label' => 'Название'],
            'name_alt' => ['label' => 'Название (альт.)'],
            'sended_at' => ['label' => 'Дата КП', 'type' => 'date'],
            'manager_id' => ['label' => 'Менеджер', 'relation' => 'manager', 'title' => 'full_name'],
            'company_id' => ['label' => 'Компания', 'relation' => 'company'],
            'partner_id' => ['label' => 'Партнёр', 'relation' => 'partner'],
            'status' => ['label' => 'Статус', 'enum' => ProposalStatus::class],
            'status_reason' => ['label' => 'Причина', 'enum' => ProposalLostReason::class],
            'status_comment' => ['label' => 'Комментарий к статусу'],
            'crm_deal_id' => ['label' => 'Главная сделка Битрикс24'],
            'nds' => ['label' => 'НДС, %'],
            'lang' => ['label' => 'Язык', 'options' => ['ru' => 'Русский', 'en' => 'English']],
            'currency_slug' => ['label' => 'Валюта', 'relation' => 'currency', 'title' => 'name'],
            'currency_rate' => ['label' => 'Курс валюты'],
            'task' => ['label' => 'ТЗ', 'type' => 'html'],
        ];
    }
}
