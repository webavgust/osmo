<?php

namespace App\Modules\Pub\ContractSpecification\Models;

use App\Models\Traits\HasLogger;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Привязка КП к спецификации.
 *
 * Ключ привязки — группа КП, а не id редакции: прикрепляется предложение
 * целиком. Дата прикрепления — момент, когда КП стало договором; по ней
 * считается показатель «КП → договор» в скоринге партнёров.
 */
class ContractSpecificationProposal extends Model
{
    use HasLogger;

    protected $table = 'contract_specification_proposals';
    public $timestamps = false;

    protected $fillable = ['contract_specification_id', 'proposal_group', 'attached_at', 'attached_by'];
    protected $casts = ['attached_at' => 'datetime'];

    public function specification()
    {
        return $this->belongsTo(ContractSpecification::class, 'contract_specification_id');
    }

    /** Последняя редакция привязанного КП */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'proposal_group', 'group');
    }

    public function author()
    {
        // withTrashed: имя не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'attached_by')->withTrashed();
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'specification';
    }

    public static function logLabel(): string
    {
        return 'КП спецификации';
    }

    public function logKey(int $index): string
    {
        return (string) $this->proposal_group;
    }

    public function logTitle(?int $index = null): string
    {
        $last = static::lastProposal((string) $this->proposal_group);

        return $last ? 'КП № ' . (trim((string) $last->number) !== '' ? $last->number : static::logText($last->name, 60)) : 'КП ' . $this->proposal_group;
    }

    public static function logFields(): array
    {
        return [
            'proposal_group' => ['label' => 'КП', 'format' => fn($value) => static::lastProposal((string) $value)?->logTitle() ?? (string) $value],
            'attached_at' => ['label' => 'Прикреплено', 'type' => 'datetime'],
            'attached_by' => ['label' => 'Кто прикрепил', 'relation' => 'author', 'title' => 'full_name'],
        ];
    }

    /**
     * Последняя редакция КП группы
     *
     * @param string $group
     * @return Proposal|null
     */
    protected static function lastProposal(string $group): ?Proposal
    {
        if ($group === '') return null;

        return Proposal::where('group', $group)->orderByDesc('iteration')->orderByDesc('id')->first();
    }
}
