<?php

namespace App\Modules\Pub\ExternalProposal\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * КП внешней системы (patch v21).
 *
 * Сейчас единственный источник — OSMOVIEW CP Generator (`osmoview_cp`).
 * `external_id` — doc-id из списка, `external_number` — номер КП у Алексея
 * (`AK528`), `payload` — полный ответ detail, `list_payload` — строка списка.
 */
class ExternalProposal extends ModuleModel
{
    public const SOURCE_OSMOVIEW_CP = 'osmoview_cp';

    /**
     * Типы лицензий (значения аксессора `license_type`): подпись и цвет плашки.
     *
     * Общий источник для ячейки таблицы и фильтра страницы, чтобы подписи с
     * цветами в них не разъехались (попап «Подробнее» пока со своим match()).
     */
    public const LICENSE_TYPES = [
        'unlimited' => ['label' => 'бессрочные', 'color' => 'success'],
        'year' => ['label' => 'годовые', 'color' => 'primary'],
        'mixed' => ['label' => 'смешанные', 'color' => 'warning'],
    ];

    protected $fillable = [
        'source', 'external_id', 'external_number', 'name', 'customer', 'cameras',
        'created_at_remote', 'updated_at_remote', 'list_payload', 'payload', 'fetched_at',
        'proposal_group', 'transferred_at', 'transferred_by',
    ];

    protected $casts = [
        'list_payload' => 'array',
        'payload' => 'array',
        'created_at_remote' => 'date',
        'updated_at_remote' => 'datetime',
        'fetched_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    /*** RELATIONS ***/

    /**
     * Наше КП, в которое запись перенесена (последняя итерация группы)
     */
    public function proposal()
    {
        return $this->hasOne(Proposal::class, 'group', 'proposal_group')->ofMany('iteration', 'max');
    }

    /**
     * Кто переносил
     */
    public function transferred_user()
    {
        // withTrashed: имя не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'transferred_by')->withTrashed();
    }

    /*** SCOPES ***/

    public function scopeSource(Builder $builder, string $source = self::SOURCE_OSMOVIEW_CP)
    {
        return $builder->where('source', $source);
    }

    public function scopeTransferred(Builder $builder, bool $flag = true)
    {
        return $flag
            ? $builder->whereNotNull('proposal_group')
            : $builder->whereNull('proposal_group');
    }

    /*** ATTRIBUTES ***/

    /**
     * Детальные данные загружены?
     */
    public function getHasPayloadAttribute(): bool
    {
        if (empty($this->payload)) return false;

        return !empty($this->payload['items'] ?? null) || !empty($this->payload['detailedWorks'] ?? null);
    }

    /**
     * Валюта из detail (нет поля — RUB)
     */
    public function getCurrencyAttribute(): string
    {
        $currency = strtoupper((string) ($this->payload['currency'] ?? ''));

        return $currency !== '' ? $currency : 'RUB';
    }

    /**
     * Тип лицензий по позициям: unlimited / year / mixed / null
     */
    public function getLicenseTypeAttribute(): ?string
    {
        $items = $this->payload['items'] ?? null;
        if (empty($items) || !is_array($items)) return null;

        $perpetual = collect($items)->filter(fn($item) => !empty($item['isPerpetual']))->count();
        $total = count($items);

        if ($perpetual === 0) return 'year';
        if ($perpetual === $total) return 'unlimited';

        return 'mixed';
    }

    /**
     * Подпись источника для плашек
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            self::SOURCE_OSMOVIEW_CP => 'OSMOVIEW CP',
            default => strtoupper($this->source),
        };
    }
}
