<?php

namespace App\Modules\Pub\Proposal\Models;

use App\Models\Traits\HasLogger;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Привязка КП к сделке Битрикса.
 *
 * У одного КП может быть несколько сделок. Одна из них — главная:
 * её id дублируется в proposals.crm_deal_id для старого кода и фильтров.
 */
class ProposalCrmDeal extends Model
{
    use HasLogger;

    protected $table = 'proposal_crm_deals';
    public $timestamps = false;

    protected $fillable = ['proposal_group', 'crm_deal_id', 'is_main', 'comment', 'linked_at', 'linked_by'];
    protected $casts = ['is_main' => 'boolean', 'linked_at' => 'datetime'];

    /**
     * Сделка Битрикса (другое соединение — связь по id вручную)
     */
    public function crm_deal()
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id', 'id');
    }

    public function author()
    {
        // withTrashed: имя не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'linked_by')->withTrashed();
    }

    /**
     * Привязки КП
     *
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forGroup(string $group)
    {
        return static::where('proposal_group', $group)
            ->orderByDesc('is_main')
            ->orderBy('id');
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logLabel(): string
    {
        return 'Сделка Битрикс24';
    }

    /** Родитель — последняя редакция КП группы (связь по group, а не belongsTo по id) */
    public function logParent(): ?Model
    {
        $group = (string) $this->proposal_group;
        if ($group === '') return null;

        return Proposal::where('group', $group)->orderByDesc('iteration')->orderByDesc('id')->first();
    }

    public function logKey(int $index): string
    {
        return (string) $this->crm_deal_id;
    }

    public function logTitle(?int $index = null): string
    {
        return 'Сделка Битрикс24 #' . $this->crm_deal_id;
    }

    public static function logIgnore(): array
    {
        return ['proposal_group'];
    }

    public static function logFields(): array
    {
        return [
            'crm_deal_id' => ['label' => 'Сделка'],
            'is_main' => ['label' => 'Главная', 'type' => 'bool'],
            'comment' => ['label' => 'Комментарий'],
            'linked_at' => ['label' => 'Привязана', 'type' => 'datetime'],
            'linked_by' => ['label' => 'Кто привязал', 'relation' => 'author', 'title' => 'full_name'],
        ];
    }
}
